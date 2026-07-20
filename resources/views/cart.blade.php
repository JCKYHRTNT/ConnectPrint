@extends('layouts.master')

@section('title', 'Cart - ConnectPrint')

@section('content')
<style>
    .cp-cart-filter-panel {
        display: grid;
        grid-template-columns: minmax(220px, 1.4fr) minmax(180px, 1fr) minmax(150px, 0.7fr) auto;
        gap: 0.75rem;
        align-items: end;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.85rem;
        background: #ffffff;
    }

    .cp-filter-actions {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
    }

    @media (max-width: 640px) {
        .cp-cart-filter-panel {
            grid-template-columns: 1fr;
        }

        .cp-filter-actions {
            justify-content: flex-start;
        }
    }
</style>

<div class="tb-card p-4">
    <h1 style="font-size:1.4rem;font-weight:700;">Cart</h1>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @error('cart') <div class="alert alert-danger">{{ $message }}</div> @enderror

    <form method="GET" action="{{ route('cart') }}" class="cp-cart-filter-panel mb-3" data-autofilter-form>
        <div>
            <label class="form-label" for="cartSearch">Search</label>
            <input class="form-control" id="cartSearch" name="q" value="{{ request('q') }}" placeholder="Artwork or creator" data-autofilter-debounce>
        </div>
        <div>
            <label class="form-label" for="cartCategory">Category</label>
            <select class="form-select" id="cartCategory" name="category" data-autofilter-instant>
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="cartSort">Sort</label>
            <select class="form-select" id="cartSort" name="sort" data-autofilter-instant>
                <option value="">Newest</option>
                <option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option>
                <option value="price_asc" @selected(request('sort') === 'price_asc')>Price low</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>Price high</option>
            </select>
        </div>
        <div class="cp-filter-actions">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('cart') }}">Clear</a>
        </div>
    </form>

    @if($items->isEmpty())
        <p class="mb-0 text-muted">{{ count(request()->except('cursor')) > 0 ? 'No matching cart items found.' : 'Your cart is empty.' }}</p>
    @else
        <div class="d-flex flex-column gap-2">
            @foreach($items as $item)
                @php($artwork = $item->artwork)
                <div class="d-flex align-items-center gap-3 border rounded p-2">
                    <img src="{{ $artwork->image_url }}" alt="{{ $artwork->name }}" style="width:86px;height:86px;object-fit:cover;border-radius:8px;">
                    <div class="flex-grow-1">
                        <div style="font-weight:700;">{{ $artwork->name }}</div>
                        <div class="text-muted small">Creator: {{ $artwork->creatorName() }}</div>
                        <div style="font-weight:700;color:var(--tb-blue);">Rp{{ number_format($artwork->price, 0, ',', '.') }}</div>
                    </div>
                    <form method="POST" action="{{ route('cart.item.update', ['item' => $item->id]) }}">
                        @csrf
                        <button class="btn btn-outline-danger btn-sm" type="submit">Remove</button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="mt-3 p-3 border rounded">
            <div class="d-flex justify-content-between mb-2">
                <strong>Total creator price</strong>
                <strong>Rp{{ number_format($total, 0, ',', '.') }}</strong>
            </div>
            <p class="small text-muted">Printbox application fees are paid separately on the Printbox website.</p>
            <form method="POST" action="{{ route('cart.checkout') }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label">Demo payment method</label>
                    <select class="form-select" name="payment_method" required>
                        <option>Demo Bank Transfer</option>
                        <option>Demo E-Wallet</option>
                        <option>Demo Payment</option>
                    </select>
                </div>
                <label class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="confirmation" value="1" required>
                    <span class="form-check-label">I understand this is a simulated payment and Printbox charges its own application fee separately.</span>
                </label>
                <button class="tb-btn-primary" type="submit">Complete simulated checkout</button>
            </form>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-autofilter-form]').forEach(function (form) {
            let timer = null;

            form.querySelectorAll('[data-autofilter-debounce]').forEach(function (input) {
                input.addEventListener('input', function () {
                    window.clearTimeout(timer);
                    timer = window.setTimeout(function () {
                        form.requestSubmit();
                    }, 450);
                });
            });

            form.querySelectorAll('[data-autofilter-instant]').forEach(function (input) {
                input.addEventListener('change', function () {
                    form.requestSubmit();
                });
            });
        });
    });
</script>
@endsection
