<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product as Artwork;
use App\Models\User;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\AppNotification;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * Show cart for logged-in user.
     */
    public function index()
    {
        if (!session('user_id')) {
            return redirect()->route('login')->with('error', 'Please login to view your cart.');
        }

        $userId = session('user_id');

        $cart = Cart::firstOrCreate(
            ['user_id' => $userId],
            ['user_id' => $userId]
        );

        $cart->load(['items.artwork.category']);

        $items = $cart->items;

        $total = $items->reduce(function ($carry, CartItem $item) {
            return $carry + ($item->artwork->price ?? 0);
        }, 0);

        $categories = Category::orderBy('name')->get();

        return view('cart', [
            'items'      => $items,
            'total'      => $total,
            'categories' => $categories,
        ]);
    }

    /**
     * Add artwork to cart.
     */
    public function add(Request $request, Artwork $artwork)
    {
        if (!session('user_id')) {
            return redirect()->route('login')->with('error', 'Please login to add items to cart.');
        }

        $userId       = session('user_id');
        $user = User::findOrFail($userId);

        if (! $artwork->canBePurchasedBy($user)) {
            return redirect()->back()->with('error', 'This artwork is not available for printable access purchase.');
        }

        $cart = Cart::firstOrCreate(
            ['user_id' => $userId],
            ['user_id' => $userId]
        );

        CartItem::updateOrCreate(
            ['cart_id' => $cart->id, 'product_id' => $artwork->id],
            ['quantity' => 1, 'creator_price_snapshot' => $artwork->price]
        );

        return redirect()
            ->route('cart')
            ->with('success', 'Printable access added to cart.');
    }

    /**
     * Update quantity for a cart item.
     */
    public function update(Request $request, CartItem $item)
    {
        if (!session('user_id')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        $item->delete();

        return redirect()
            ->route('cart')
            ->with('success', 'Artwork removed from cart.');
    }

    /**
     * Checkout: simulate payment, create purchase snapshots, and clear cart.
     */
    public function checkout(Request $request)
    {
        if (!session('user_id')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        $request->validate([
            'payment_method' => ['required', 'in:Demo Bank Transfer,Demo E-Wallet,Demo Payment'],
            'confirmation' => ['accepted'],
        ]);

        $userId = session('user_id');
        $user = User::findOrFail($userId);

        // Get user's cart
        $cart = Cart::with(['items.artwork'])
            ->where('user_id', $userId)
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', 'Your cart is empty.');
        }

        $purchase = DB::transaction(function () use ($cart, $user) {
            foreach ($cart->items as $item) {
                if (! $item->artwork || ! $item->artwork->canBePurchasedBy($user)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cart' => 'One or more artworks in the cart are no longer purchasable.',
                    ]);
                }
            }

            $total = $cart->items->sum(fn ($item) => (int) $item->artwork->price);
            $purchase = Purchase::create([
                'user_id' => $user->id,
                'purchase_number' => 'PBX-' . now()->format('Ymd') . '-' . str_pad((string) (Purchase::count() + 1), 4, '0', STR_PAD_LEFT),
                'status' => 'completed',
                'payment_status' => 'simulated_paid',
                'subtotal' => $total,
                'total' => $total,
            ]);

            foreach ($cart->items as $item) {
                $artwork = $item->artwork;

                if (!$artwork) {
                    continue;
                }

                $purchase->items()->create([
                    'product_id' => $artwork->id,
                    'creator_id' => $artwork->user_id,
                    'artwork_title_snapshot' => $artwork->name,
                    'creator_name_snapshot' => $artwork->creatorName(),
                    'creator_price' => $artwork->price,
                    'original_path_snapshot' => $artwork->original_path,
                ]);

                if ($artwork->user_id) {
                    AppNotification::create([
                        'user_id' => $artwork->user_id,
                        'message' => $user->name . ' purchased printable access to "' . $artwork->name . '".',
                        'url' => route('account', ['tab' => 'history']),
                    ]);
                }
            }

            AppNotification::create([
                'user_id' => $user->id,
                'message' => 'Your purchase ' . $purchase->purchase_number . ' is complete.',
                'url' => route('purchases.show', ['purchase' => $purchase->id]),
            ]);

            $cart->items()->delete();

            return $purchase;
        });

        return redirect()
            ->route('purchases.show', ['purchase' => $purchase->id])
            ->with('success', 'Simulation only - no real payment was processed.');
    }
}
