<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product as Artwork;
use App\Models\PurchaseItem;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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
            return redirect()->route('account.admin', ['username' => $expectedSlug]);
        }

        if ($request->expectsJson() && $request->input('tab') === 'images') {
            return response()->json($this->accountImagesCursorPayload($user, $request));
        }

        return view('account', $this->accountViewData($user, true, $request));
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
            $baseRoute = $request->routeIs('account.admin.update') ? 'account.admin' : 'account';
            $routeParams = $baseRoute === 'account.admin' ? ['username' => $expectedSlug] : [];

            return redirect()->route($baseRoute, $routeParams);
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

        $newSlug   = $user->slug;
        $baseRoute = $request->routeIs('account.admin.update') ? 'account.admin' : 'account';

        $routeParams = $baseRoute === 'account.admin' ? ['username' => $newSlug] : [];

        return redirect()
            ->route($baseRoute, $routeParams)
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
            $baseRoute = $request->routeIs('account.admin.delete') ? 'account.admin' : 'account';

            $routeParams = $baseRoute === 'account.admin' ? ['username' => $expectedSlug] : [];

            return redirect()->route($baseRoute, $routeParams);
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

        $artworks = $this->accountImagesQuery($user)
            ->cursorPaginate(9)
            ->withQueryString();

        $purchases = $user->purchases()
            ->with('items')
            ->latest()
            ->get();

        $purchasedItems = PurchaseItem::with(['purchase', 'artwork.user'])
            ->whereHas('purchase', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('status', 'completed'))
            ->latest()
            ->get();

        $sales = PurchaseItem::with(['purchase.user', 'artwork'])
            ->where('creator_id', $user->id)
            ->latest()
            ->get();

        return [
            'user' => $user,
            'isAdminPage' => $isAdminPage,
            'activeTab' => $activeTab,
            'artworks' => $artworks,
            'purchases' => $purchases,
            'purchasedItems' => $purchasedItems,
            'sales' => $sales,
            'artworkCount' => Artwork::where('user_id', $user->id)->count(),
            'publicArtworkCount' => Artwork::where('user_id', $user->id)
                ->where('visibility', 'public')
                ->whereNotIn('moderation_status', ['draft', 'rejected'])
                ->count(),
            'purchaseCount' => $user->purchases()->count(),
            'saleCount' => PurchaseItem::where('creator_id', $user->id)->count(),
        ];
    }

    private function accountImagesQuery(User $user)
    {
        return Artwork::with(['category', 'tags', 'user'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    private function accountImagesCursorPayload(User $user, Request $request): array
    {
        $artworks = $this->accountImagesQuery($user)
            ->cursorPaginate(9)
            ->withQueryString();

        return [
            'data' => collect($artworks->items())
                ->map(fn ($artwork) => view('artworks.partials.profile-image-card', ['artwork' => $artwork])->render())
                ->values()
                ->all(),
            'next_cursor' => $artworks->nextCursor()?->encode(),
            'has_more' => $artworks->hasMorePages(),
            'total' => Artwork::where('user_id', $user->id)->count(),
        ];
    }
}
