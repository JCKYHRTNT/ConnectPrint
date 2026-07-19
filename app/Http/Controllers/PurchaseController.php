<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;

class PurchaseController extends Controller
{
    public function index()
    {
        $user = User::findOrFail(session('user_id'));
        return view('purchases.index', [
            'purchases' => $user->purchases()->with('items')->latest()->paginate(10),
        ]);
    }

    public function show(string $username, Purchase $purchase)
    {
        $user = User::findOrFail(session('user_id'));
        abort_unless((int) $purchase->user_id === (int) $user->id || $user->role === 'admin', 403);

        return view('purchases.show', [
            'purchase' => $purchase->load('items.artwork'),
            'viewer' => $user,
        ]);
    }

    public function purchasedArtworks()
    {
        $user = User::findOrFail(session('user_id'));
        $items = PurchaseItem::with(['purchase', 'artwork.user'])
            ->whereHas('purchase', fn ($query) => $query->where('user_id', $user->id)->where('status', 'completed'))
            ->latest()
            ->paginate(12);

        return view('purchases.library', [
            'items' => $items,
            'viewer' => $user,
        ]);
    }

    public function sales()
    {
        $user = User::findOrFail(session('user_id'));
        $items = PurchaseItem::with(['purchase.user', 'artwork'])
            ->where('creator_id', $user->id)
            ->latest()
            ->paginate(20);

        return view('purchases.sales', [
            'items' => $items,
            'total' => (clone $items->getCollection())->sum('creator_price'),
        ]);
    }
}
