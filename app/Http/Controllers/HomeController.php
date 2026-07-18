<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product as Artwork;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    /**
     * Guest + user home.
     */
    public function home(Request $request)
    {
        $categoryId = $request->filled('category')
            ? (int) $request->input('category')
            : null;

        $query = $request->input('q');

        // Load categories
        $categories = Category::orderBy('name')->get();

        $categoryNames = $categories
            ->pluck('name', 'id')
            ->toArray();

        $artworksQuery = Artwork::with(['category', 'user', 'tags'])
            ->where('visibility', 'public')
            ->where('moderation_status', 'approved')
            ->whereNull('archived_at');

        // FILTER BY CATEGORY
        if (!is_null($categoryId)) {
            $artworksQuery->where('category_id', $categoryId);
        }

        // FILTER BY SEARCH
        if ($query) {
            $q = strtolower($query);
            $artworksQuery->where(function ($subQuery) use ($q) {
                $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"])
                    ->orWhereHas('user', fn ($creatorQuery) => $creatorQuery->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"]))
                    ->orWhereHas('tags', fn ($tagQuery) => $tagQuery->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"]));
            });
        }

        if ($request->filled('printable')) {
            $artworksQuery->where('is_printable', $request->input('printable') === 'printable');
        }

        match ($request->input('sort')) {
            'price_asc' => $artworksQuery->orderBy('price'),
            'price_desc' => $artworksQuery->orderByDesc('price'),
            default => $artworksQuery->latest(),
        };

        $artworks = $artworksQuery->paginate(12)->withQueryString();
        $viewer = session('user_id') ? User::find(session('user_id')) : null;
        $ownArtworks = collect();
        $ownArtworkCount = 0;
        $pendingOwnArtworkCount = 0;

        if ($viewer) {
            $ownArtworks = Artwork::with('category')
                ->where('user_id', $viewer->id)
                ->latest()
                ->take(3)
                ->get();

            $ownArtworkCount = Artwork::where('user_id', $viewer->id)->count();
            $pendingOwnArtworkCount = Artwork::where('user_id', $viewer->id)
                ->where('moderation_status', 'pending')
                ->count();
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
            'query'            => $query,
            'recentCategories' => $recentCategories,
            'categories'       => $categories,
            'categoryNames'    => $categoryNames,
            'viewer'           => $viewer,
            'ownArtworks'      => $ownArtworks,
            'ownArtworkCount'  => $ownArtworkCount,
            'pendingOwnArtworkCount' => $pendingOwnArtworkCount,
        ]);
    }

    /**
     * User home: /u/{username}
     */
    public function homeForUser(Request $request, string $username)
    {
        if (!session('user_id')) {
            return redirect()->route('login');
        }

        $sessionName  = session('name');
        $expectedSlug = Str::slug($sessionName);

        // Prevent accessing another user’s URL
        if ($username !== $expectedSlug) {
            return redirect()->route('home.user', [
                'username' => $expectedSlug,
            ] + $request->query());
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
        $isOwner = $viewer && (int) $viewer->id === (int) $artwork->user_id;
        $isAdmin = $viewer && $viewer->role === 'admin';

        if (! $artwork->isApprovedPublic() && ! $isOwner && ! $isAdmin) {
            abort(403);
        }

        return view('artworkdetail', [
            'artwork' => $artwork,
            'viewer' => $viewer,
            'canPurchase' => $artwork->canBePurchasedBy($viewer),
        ]);
    }
}
