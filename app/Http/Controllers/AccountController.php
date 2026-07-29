<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product as Artwork;
use App\Models\PurchaseItem;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    /**
     * User account page: /account
     */
    public function userAccount(Request $request, ?string $username = null)
    {
        if (!session('user_id')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        $user         = User::findOrFail(session('user_id'));
        $expectedSlug = $user->slug;

        if ($username !== null && $username !== $expectedSlug) {
            return redirect()->route('account');
        }

        if ($request->expectsJson() && $request->input('tab') === 'images') {
            return response()->json($this->accountImagesCursorPayload($user, $request));
        }

        if ($request->expectsJson() && $request->input('tab') === 'history') {
            return response()->json($this->accountHistoryCursorPayload($user, $request));
        }

        return view('account', $this->accountViewData($user, false, $request));
    }

    /**
     * Admin account page: /a/{username}/account
     */
    public function adminAccount(Request $request, string $username)
    {
        if (!session('user_id')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        $user         = User::findOrFail(session('user_id'));
        $expectedSlug = $user->slug;

        if ($username !== $expectedSlug) {
            return redirect()->route('account', $request->query());
        }

        return redirect()->route('account', $request->query());
    }

    /**
     * Update account (name + email, password confirmation).
     */
    public function update(Request $request, ?string $username = null)
    {
        if (!session('user_id')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        $user         = User::findOrFail(session('user_id'));
        $expectedSlug = $user->slug;

        if ($username !== null && $username !== $expectedSlug) {
            return redirect()->route('account');
        }

        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password_confirmation' => ['required', 'string'],
        ]);

        if (!Hash::check($data['password_confirmation'], $user->password)) {
            return back()->with('error', 'Incorrect password.');
        }

        $user->name  = $data['name'];
        $user->email = $data['email'];
        $user->save();

        // Update session name
        session(['name' => $user->name]);

        return redirect()
            ->route('account')
            ->with('success', 'Account updated.');
    }

    /**
     * Delete account (password confirmation).
     */
    public function destroy(Request $request, ?string $username = null)
    {
        if (!session('user_id')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        $user         = User::findOrFail(session('user_id'));
        $expectedSlug = $user->slug;

        if ($username !== null && $username !== $expectedSlug) {
            return redirect()->route('account');
        }

        $data = $request->validate([
            'password_confirmation' => ['required', 'string'],
        ]);

        if (!Hash::check($data['password_confirmation'], $user->password)) {
            return back()->with('error', 'Incorrect password.');
        }

        $user->delete();
        session()->flush();

        return redirect('/')->with('success', 'Account deleted.');
    }

    private function accountViewData(User $user, bool $isAdminPage, Request $request): array
    {
        $activeTab = $request->input('tab', 'account');
        if (! in_array($activeTab, ['account', 'images', 'history'], true)) {
            $activeTab = 'account';
        }

        $historyType = $request->input('history', 'sold');
        if (! in_array($historyType, ['sold', 'bought'], true)) {
            $historyType = 'sold';
        }

        $selectedTags = $this->selectedTagNames($request);

        $artworks = $this->accountImagesQuery($user, $request)
            ->cursorPaginate(9)
            ->withQueryString();

        $historyItems = $activeTab === 'history'
            ? $this->accountHistoryQuery($user, $historyType, $request)->cursorPaginate(10)->withQueryString()
            : null;

        return [
            'user' => $user,
            'isAdminPage' => $isAdminPage,
            'activeTab' => $activeTab,
            'historyType' => $historyType,
            'historyItems' => $historyItems,
            'artworks' => $artworks,
            'categories' => Category::orderBy('name')->get(),
            'suggestedTags' => $this->suggestedTagNames($user),
            'selectedTags' => $selectedTags->all(),
            'artworkCount' => Artwork::where('user_id', $user->id)->count(),
            'publicArtworkCount' => Artwork::where('user_id', $user->id)
                ->where('visibility', 'public')
                ->whereNull('archived_at')
                ->whereNotIn('moderation_status', ['draft', 'rejected'])
                ->count(),
            'purchaseCount' => $user->purchases()->count(),
            'saleCount' => PurchaseItem::where('creator_id', $user->id)->count(),
        ];
    }

    private function accountImagesQuery(User $user, Request $request)
    {
        $query = Artwork::with(['category', 'tags', 'user'])
            ->withCount([
                'purchaseItems as completed_sales_count' => fn ($query) => $query
                    ->where('creator_price', '>', 0)
                    ->whereHas('purchase', fn ($purchaseQuery) => $purchaseQuery
                        ->where('status', 'completed')
                        ->where('user_id', '!=', $user->id)),
            ])
            ->where('user_id', $user->id);

        $search = trim((string) $request->input('q', ''));
        $selectedTags = $this->selectedTagNames($request);

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhereHas('tags', fn ($tagQuery) => $tagQuery->where('name', 'like', '%' . ltrim($search, '#') . '%'));
            });
        }

        if ($selectedTags->isNotEmpty()) {
            $query->whereHas('tags', fn ($tagQuery) => $tagQuery->whereIn('name', $selectedTags->all()));
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }

        if ($request->input('visibility') === 'archived') {
            $query->whereNotNull('archived_at');
        } elseif (in_array($request->input('visibility'), ['public', 'unlisted', 'private'], true)) {
            $query->where('visibility', $request->input('visibility'))->whereNull('archived_at');
        }

        if ($request->input('printable') === 'printable') {
            $query->where('is_printable', true);
        } elseif ($request->input('printable') === 'display') {
            $query->where('is_printable', false);
        }

        return $this->applyArtworkSort($query, $request->input('sort'));
    }

    private function accountImagesCursorPayload(User $user, Request $request): array
    {
        $artworks = $this->accountImagesQuery($user, $request)
            ->cursorPaginate(9)
            ->withQueryString();

        return [
            'data' => collect($artworks->items())
                ->map(fn ($artwork) => view('artworks.partials.profile-image-card', ['artwork' => $artwork])->render())
                ->values()
                ->all(),
            'next_cursor' => $artworks->nextCursor()?->encode(),
            'has_more' => $artworks->hasMorePages(),
            'total' => null,
        ];
    }

    private function accountHistoryQuery(User $user, string $historyType, Request $request)
    {
        $search = trim((string) $request->input('q', ''));

        if ($historyType === 'bought') {
            $query = PurchaseItem::with(['purchase', 'artwork.user'])
                ->whereHas('purchase', fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where('status', 'completed'));

            if ($search !== '') {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('artwork_title_snapshot', 'like', '%' . $search . '%')
                        ->orWhere('creator_name_snapshot', 'like', '%' . $search . '%')
                        ->orWhereHas('purchase', fn ($purchaseQuery) => $purchaseQuery->where('purchase_number', 'like', '%' . $search . '%'));
                });
            }

            return $this->applyHistorySort($query, $request->input('sort'));
        }

        $query = PurchaseItem::with(['purchase.user', 'artwork'])
            ->where('creator_id', $user->id)
            ->where('creator_price', '>', 0)
            ->whereHas('purchase', fn ($purchaseQuery) => $purchaseQuery
                ->where('status', 'completed')
                ->where('user_id', '!=', $user->id));

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('artwork_title_snapshot', 'like', '%' . $search . '%')
                    ->orWhereHas('purchase', fn ($purchaseQuery) => $purchaseQuery
                        ->where('purchase_number', 'like', '%' . $search . '%')
                        ->orWhereHas('user', fn ($buyerQuery) => $buyerQuery->where('name', 'like', '%' . $search . '%')));
            });
        }

        return $this->applyHistorySort($query, $request->input('sort'));
    }

    private function accountHistoryCursorPayload(User $user, Request $request): array
    {
        $historyType = $request->input('history', 'sold');
        if (! in_array($historyType, ['sold', 'bought'], true)) {
            $historyType = 'sold';
        }

        $items = $this->accountHistoryQuery($user, $historyType, $request)
            ->cursorPaginate(10)
            ->withQueryString();

        $partial = $historyType === 'bought'
            ? 'account.partials.history-bought-row'
            : 'account.partials.history-sold-row';

        return [
            'data' => collect($items->items())
                ->map(fn ($item) => view($partial, ['item' => $item, 'user' => $user])->render())
                ->values()
                ->all(),
            'next_cursor' => $items->nextCursor()?->encode(),
            'has_more' => $items->hasMorePages(),
            'total' => null,
        ];
    }

    private function applyArtworkSort($query, ?string $sort)
    {
        return match ($sort) {
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'price_asc' => $query->orderBy('price')->orderByDesc('created_at')->orderByDesc('id'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('created_at')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };
    }

    private function applyHistorySort($query, ?string $sort)
    {
        return match ($sort) {
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'price_asc' => $query->orderBy('creator_price')->orderByDesc('created_at')->orderByDesc('id'),
            'price_desc' => $query->orderByDesc('creator_price')->orderByDesc('created_at')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };
    }

    private function selectedTagNames(Request $request)
    {
        return collect((array) $request->input('tags', []))
            ->map(fn ($tag) => Str::of($tag)->ltrim('#')->replaceMatches('/\s+/', '')->lower()->toString())
            ->filter()
            ->unique()
            ->values();
    }

    private function suggestedTagNames(User $user): array
    {
        $fallback = collect(['digitalart', 'fanart', 'magic', 'digitalpainting', 'wallpaper', 'animedrawing', 'adoptable']);

        return Tag::whereHas('artworks', fn ($query) => $query
                ->where('user_id', $user->id)
                ->orWhereIn('visibility', ['public', 'unlisted']))
            ->orderBy('name')
            ->pluck('name')
            ->merge($fallback)
            ->map(fn ($tag) => Str::of($tag)->replace(' ', '')->lower()->toString())
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }
}
