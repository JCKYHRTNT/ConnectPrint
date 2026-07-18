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
     * User account page: /u/{username}/account
     */
    public function userAccount(Request $request, string $username)
    {
        if (!session('user_id')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        $user         = User::findOrFail(session('user_id'));
        $expectedSlug = $user->slug;

        if ($username !== $expectedSlug) {
            return redirect()->route('account', ['username' => $expectedSlug]);
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

        return view('account', $this->accountViewData($user, true, $request));
    }

    /**
     * Update account (name + email, password confirmation).
     */
    public function update(Request $request, string $username)
    {
        if (!session('user_id')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        $user         = User::findOrFail(session('user_id'));
        $expectedSlug = $user->slug;

        if ($username !== $expectedSlug) {
            $baseRoute = $request->routeIs('account.admin.update') ? 'account.admin' : 'account';

            return redirect()->route($baseRoute, ['username' => $expectedSlug]);
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

        return redirect()
            ->route($baseRoute, ['username' => $newSlug])
            ->with('success', 'Account updated.');
    }

    /**
     * Delete account (password confirmation).
     */
    public function destroy(Request $request, string $username)
    {
        if (!session('user_id')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        $user         = User::findOrFail(session('user_id'));
        $expectedSlug = $user->slug;

        if ($username !== $expectedSlug) {
            $baseRoute = $request->routeIs('account.admin.delete') ? 'account.admin' : 'account';

            return redirect()->route($baseRoute, ['username' => $expectedSlug]);
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

        $artworks = Artwork::with(['category', 'tags'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

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
                ->where('moderation_status', 'approved')
                ->count(),
            'purchaseCount' => $user->purchases()->count(),
            'saleCount' => PurchaseItem::where('creator_id', $user->id)->count(),
        ];
    }
}
