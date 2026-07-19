<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;

class PurchaseController extends Controller
{
    public function show(Purchase $purchase)
    {
        $user = User::findOrFail(session('user_id'));
        abort_unless((int) $purchase->user_id === (int) $user->id, 403);

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

}
