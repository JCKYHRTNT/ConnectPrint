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
use App\Models\AppSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    /**
     * Show cart for logged-in user.
     */
    public function index(Request $request)
    {
        if (!session('user_id')) {
            return redirect()->route('login')->with('error', 'Please login to view your cart.');
        }

        $userId = session('user_id');
        $user = User::findOrFail($userId);

        $cart = Cart::firstOrCreate(
            ['user_id' => $userId],
            ['user_id' => $userId]
        );

        $allItems = $cart->items()->with(['artwork.user', 'artwork.category'])->get();

        $creatorSubtotal = $allItems->reduce(function ($carry, CartItem $item) use ($user) {
            if (! $item->artwork) {
                return $carry;
            }

            if ($item->printbox_requested && $item->artwork->canDownloadFileBy($user)) {
                return $carry;
            }

            return $carry + ($item->artwork->price ?? 0);
        }, 0);
        $printboxFee = $allItems->sum(fn (CartItem $item) => $this->printboxFeeForItem($item));
        $applicationFee = $allItems->isEmpty() ? 0 : AppSetting::integer('application_fee', AppSetting::DEFAULT_APPLICATION_FEE);
        $total = $creatorSubtotal + $applicationFee + $printboxFee;

        $items = $this->cartItemsQuery($cart, $request)->get();
        $categories = Category::orderBy('name')->get();

        return view('cart', [
            'items'      => $items,
            'viewer' => $user,
            'creatorSubtotal' => $creatorSubtotal,
            'applicationFee' => $applicationFee,
            'printboxFee' => $printboxFee,
            'printboxRates' => AppSetting::printboxRates(),
            'total'      => $total,
            'categories' => $categories,
        ]);
    }

    private function cartItemsQuery(Cart $cart, Request $request)
    {
        $query = $cart->items()->with(['artwork.user', 'artwork.category']);
        $search = trim((string) $request->input('q', ''));

        if ($search !== '') {
            $query->whereHas('artwork', function ($artworkQuery) use ($search) {
                $artworkQuery
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%' . $search . '%'));
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('artwork', fn ($artworkQuery) => $artworkQuery->where('category_id', $request->integer('category')));
        }

        if (in_array($request->input('sort'), ['price_asc', 'price_desc'], true)) {
            $query->join('products', 'cart_items.product_id', '=', 'products.id')
                ->select('cart_items.*')
                ->orderBy('products.price', $request->input('sort') === 'price_asc' ? 'asc' : 'desc')
                ->orderByDesc('cart_items.created_at')
                ->orderByDesc('cart_items.id');

            return $query;
        }

        return match ($request->input('sort')) {
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };
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
        $isOwner = (int) $artwork->user_id === (int) $user->id;
        $printboxData = $this->printboxData($request);
        $isPrintOnly = $request->boolean('printbox_requested')
            && $artwork->canDownloadFileBy($user);

        if (! $isPrintOnly && ! $artwork->canBePurchasedBy($user)) {
            return redirect()->back()->with('error', 'This artwork is not available for printable access purchase.');
        }

        if ($isOwner && ! $isPrintOnly) {
            return redirect()->back()->with('error', 'Select Printbox printing to add your own artwork to cart.');
        }

        $cart = Cart::firstOrCreate(
            ['user_id' => $userId],
            ['user_id' => $userId]
        );

        CartItem::updateOrCreate(
            ['cart_id' => $cart->id, 'product_id' => $artwork->id],
            [
                'quantity' => 1,
                'printbox_requested' => $request->boolean('printbox_requested'),
                'printbox_mode' => $request->boolean('printbox_requested') ? $printboxData['mode'] : null,
                'printbox_sheet_count' => $request->boolean('printbox_requested') ? $printboxData['sheet_count'] : 1,
            ]
        );

        return redirect()
            ->route('cart')
            ->with('success', $isPrintOnly ? 'Printbox printing added to cart.' : 'Printable access added to cart.');
    }

    /**
     * Update quantity for a cart item.
     */
    public function update(Request $request, CartItem $item)
    {
        if (!session('user_id')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        abort_unless($item->cart && (int) $item->cart->user_id === (int) session('user_id'), 403);

        $item->delete();

        return redirect()
            ->route('cart')
            ->with('success', 'Artwork removed from cart.');
    }

    public function updatePrintbox(Request $request, CartItem $item)
    {
        if (!session('user_id')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        abort_unless($item->cart && (int) $item->cart->user_id === (int) session('user_id'), 403);

        $item->load('artwork');
        $user = User::findOrFail(session('user_id'));
        $mustStayPrintOnly = $item->artwork && $item->artwork->canDownloadFileBy($user);
        $wantsPrintbox = $mustStayPrintOnly || $request->boolean('printbox_requested');
        $printboxData = $this->printboxData($request, $item);

        $item->update([
            'printbox_requested' => $wantsPrintbox,
            'printbox_mode' => $wantsPrintbox ? $printboxData['mode'] : null,
            'printbox_sheet_count' => $wantsPrintbox ? $printboxData['sheet_count'] : 1,
        ]);

        return redirect()
            ->route('cart')
            ->with('success', 'Printbox option updated.');
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
            'payment_method' => ['required', 'in:E-Wallet,QRIS'],
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

        $purchase = DB::transaction(function () use ($cart, $user, $request) {
            foreach ($cart->items as $item) {
                $artwork = $item->artwork;

                if (! $artwork) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cart' => 'One or more artworks in the cart are no longer available.',
                    ]);
                }

                $isPrintOnly = $item->printbox_requested
                    && $artwork->canDownloadFileBy($user);

                if ($isPrintOnly) {
                    continue;
                }

                if (! $artwork->canBePurchasedBy($user)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cart' => 'One or more artworks in the cart are no longer purchasable.',
                    ]);
                }
            }

            $creatorSubtotal = $cart->items->sum(function ($item) use ($user) {
                if (! $item->artwork) {
                    return 0;
                }

                if ($item->printbox_requested && $item->artwork->canDownloadFileBy($user)) {
                    return 0;
                }

                return (int) $item->artwork->price;
            });
            $printboxFee = $cart->items->sum(fn ($item) => $this->printboxFeeForItem($item));
            $applicationFee = AppSetting::integer('application_fee', AppSetting::DEFAULT_APPLICATION_FEE);
            $total = $creatorSubtotal + $applicationFee + $printboxFee;
            $purchase = Purchase::create([
                'user_id' => $user->id,
                'purchase_number' => 'PBX-' . now()->format('Ymd') . '-' . str_pad((string) (Purchase::count() + 1), 4, '0', STR_PAD_LEFT),
                'status' => 'completed',
                'payment_status' => 'simulated_paid',
                'payment_method' => $request->input('payment_method'),
                'subtotal' => $creatorSubtotal,
                'application_fee' => $applicationFee,
                'printbox_fee' => $printboxFee,
                'total' => $total,
            ]);

            foreach ($cart->items as $item) {
                $artwork = $item->artwork;

                if (!$artwork) {
                    continue;
                }

                $itemPrintboxFee = $this->printboxFeeForItem($item);
                $itemCreatorPrice = $item->printbox_requested && $artwork->canDownloadFileBy($user)
                    ? 0
                    : (int) $artwork->price;

                $purchase->items()->create([
                    'product_id' => $artwork->id,
                    'creator_id' => $artwork->user_id,
                    'artwork_title_snapshot' => $artwork->name,
                    'creator_name_snapshot' => $artwork->creatorName(),
                    'creator_price' => $itemCreatorPrice,
                    'printbox_fee' => $itemPrintboxFee,
                    'printbox_requested' => $itemPrintboxFee > 0,
                    'printbox_mode' => $itemPrintboxFee > 0 ? $item->printbox_mode : null,
                    'printbox_sheet_count' => $itemPrintboxFee > 0 ? $item->printbox_sheet_count : 1,
                    'original_path_snapshot' => $artwork->original_path,
                ]);

                if ($artwork->user_id && $itemCreatorPrice > 0) {
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

    private function printboxData(Request $request, ?CartItem $item = null): array
    {
        $mode = $request->input('printbox_mode', $item?->printbox_mode ?? 'bw');
        $sheetCount = (int) $request->input('printbox_sheet_count', $item?->printbox_sheet_count ?? 1);

        validator([
            'printbox_mode' => $mode,
            'printbox_sheet_count' => $sheetCount,
        ], [
            'printbox_mode' => ['required', Rule::in(['bw', 'color'])],
            'printbox_sheet_count' => ['required', 'integer', 'min:1', 'max:200'],
        ])->validate();

        return [
            'mode' => $mode,
            'sheet_count' => $sheetCount,
        ];
    }

    private function printboxFeeForItem(CartItem $item): int
    {
        if (! $item->printbox_requested) {
            return 0;
        }

        return $this->printboxFee((string) ($item->printbox_mode ?: 'bw'), (int) ($item->printbox_sheet_count ?: 1));
    }

    private function printboxFee(string $mode, int $sheetCount): int
    {
        $rates = AppSetting::printboxRates();

        if ($mode === 'bw' && $sheetCount >= 10) {
            return $sheetCount * $rates['bw_bulk'];
        }

        return $sheetCount * ($mode === 'color' ? $rates['color'] : $rates['bw_low']);
    }
}
