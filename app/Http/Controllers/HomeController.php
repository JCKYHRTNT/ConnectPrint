<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product as Artwork;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Models\AppSetting;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    /**
     * Guest + user home.
     */
    public function home(Request $request)
    {
        $categoryIds = collect((array) $request->input('category', []))
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $categoryId = $categoryIds->first();

        $query = $request->input('q');
        $selectedTags = collect((array) $request->input('tags', []))
            ->map(fn ($tag) => Str::of($tag)->ltrim('#')->replaceMatches('/\s+/', '')->lower()->toString())
            ->filter()
            ->unique()
            ->values();

        // Load categories
        $categories = Category::orderBy('name')->get();

        $categoryNames = $categories
            ->pluck('name', 'id')
            ->toArray();

        $artworksQuery = Artwork::with(['category', 'user', 'tags'])
            ->where('visibility', 'public')
            ->whereNotIn('moderation_status', ['draft', 'rejected']);

        // FILTER BY CATEGORY
        if ($categoryIds->isNotEmpty()) {
            $artworksQuery->whereIn('category_id', $categoryIds->all());
        }

        // FILTER BY SEARCH
        if ($query) {
            $q = strtolower(trim($query));

            if (str_starts_with($q, '#')) {
                $tagQueryText = ltrim($q, '#');
                $artworksQuery->whereHas('tags', fn ($tagQuery) => $tagQuery->whereRaw('LOWER(name) LIKE ?', ["%{$tagQueryText}%"]));
            } else {
                $artworksQuery->where(function ($subQuery) use ($q) {
                    $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"])
                        ->orWhereHas('user', fn ($creatorQuery) => $creatorQuery->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"]))
                        ->orWhereHas('tags', fn ($tagQuery) => $tagQuery->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"]));
                });
            }
        }

        if ($selectedTags->isNotEmpty()) {
            $artworksQuery->whereHas('tags', function ($tagQuery) use ($selectedTags) {
                $tagQuery->whereIn('name', $selectedTags->all());
            });
        }

        if ($request->filled('printable')) {
            $artworksQuery->where('is_printable', $request->input('printable') === 'printable');
        }

        match ($request->input('sort')) {
            'price_asc' => $artworksQuery->orderBy('price')->orderBy('id'),
            'price_desc' => $artworksQuery->orderByDesc('price')->orderByDesc('id'),
            default => $artworksQuery->orderByDesc('created_at')->orderByDesc('id'),
        };

        $artworks = $artworksQuery->cursorPaginate(12)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($this->cursorPayload($artworks, 'artworks.partials.marketplace-card'));
        }

        $viewer = session('user_id') ? User::find(session('user_id')) : null;
        $ownArtworks = collect();
        $ownArtworkCount = 0;

        if ($viewer) {
            $ownArtworks = Artwork::with('category')
                ->where('user_id', $viewer->id)
                ->latest()
                ->take(3)
                ->get();

            $ownArtworkCount = Artwork::where('user_id', $viewer->id)->count();
        }

        // RECENT CATEGORIES (max 3)
        $recentCategories = $categories;

        if (!is_null($categoryId) && $categories->pluck('id')->contains($categoryId)) {
            $selected = $categories->firstWhere('id', $categoryId);

            $recentCategories = collect([$selected])
                ->merge($categories->where('id', '!=', $categoryId));
        }

        $recentCategories = $recentCategories
            ->take(3)
            ->map(fn ($cat) => ['id' => $cat->id, 'name' => $cat->name])
            ->values()
            ->all();

        return view('home', [
            'artworks'         => $artworks,
            'categoryId'       => $categoryId,
            'categoryIds'      => $categoryIds->all(),
            'query'            => $query,
            'recentCategories' => $recentCategories,
            'categories'       => $categories,
            'categoryNames'    => $categoryNames,
            'suggestedTags'    => $this->suggestedTagNames(),
            'selectedTags'     => $selectedTags->all(),
            'viewer'           => $viewer,
            'ownArtworks'      => $ownArtworks,
            'ownArtworkCount'  => $ownArtworkCount,
        ]);
    }

    private function suggestedTagNames(): array
    {
        $fallback = collect(['digitalart', 'fanart', 'magic', 'digitalpainting', 'wallpaper', 'animedrawing', 'adoptable']);

        return Tag::whereHas('artworks', fn ($query) => $query->whereIn('visibility', ['public', 'unlisted']))
            ->orderBy('name')
            ->pluck('name')
            ->merge($fallback)
            ->map(fn ($tag) => Str::of($tag)->replace(' ', '')->lower()->toString())
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }

    private function cursorPayload($paginator, string $partial): array
    {
        return [
            'data' => collect($paginator->items())
                ->map(fn ($artwork) => view($partial, ['artwork' => $artwork])->render())
                ->values()
                ->all(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'has_more' => $paginator->hasMorePages(),
            'total' => null,
        ];
    }

    /**
     * User home: /home
     */
    public function homeForUser(Request $request, ?string $username = null)
    {
        if (!session('user_id')) {
            return redirect()->route('login');
        }

        $sessionName  = session('name');
        $expectedSlug = Str::slug($sessionName);

        // Prevent accessing another user's URL
        if ($username !== null && $username !== $expectedSlug) {
            return redirect()->route('home.user', $request->query());
        }

        return $this->home($request);
    }

    /**
     * Guest / user artwork detail.
     */
    public function artworkDetail($usernameOrId, $maybeId = null)
    {
        $id = $maybeId ?? $usernameOrId;

        $artwork = Artwork::with(['category', 'user', 'tags'])->findOrFail((int) $id);
        $viewer = session('user_id') ? User::find(session('user_id')) : null;

        if (! $artwork->canBeViewedBy($viewer)) {
            abort(403);
        }

        return view('artworkdetail', [
            'artwork' => $artwork,
            'viewer' => $viewer,
            'canPurchase' => $artwork->canBePurchasedBy($viewer),
            'printboxRates' => AppSetting::printboxRates(),
        ]);
    }
}
