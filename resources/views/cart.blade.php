@extends('layouts.master')

@section('title', 'Cart - ConnectPrint')

@section('content')
@php
    $printboxRates = $printboxRates ?? ['bw_low' => 750, 'bw_bulk' => 500, 'color' => 750];
    $cursorQuery = collect(request()->except('cursor'))
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->all();
    $cursorEndpoint = route('cart') . ($cursorQuery ? '?' . http_build_query($cursorQuery) : '');
@endphp
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

    .cp-cart-printbox-panel {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.65rem;
        margin-top: 0.5rem;
        max-width: 28rem;
    }

    .cp-cart-printbox-grid {
        display: grid;
        grid-template-columns: minmax(160px, 1fr) minmax(90px, 0.45fr);
        gap: 0.5rem;
        align-items: end;
    }

    .cp-printbox-rates {
        color: var(--tb-gray-text);
        font-size: 0.75rem;
        margin-top: 0.4rem;
    }

    @media (max-width: 640px) {
        .cp-cart-filter-panel {
            grid-template-columns: 1fr;
        }

        .cp-cart-printbox-grid {
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
        <div
            data-cursor-feed
            data-cursor-endpoint="{{ $cursorEndpoint }}"
            data-next-cursor="{{ $items->nextCursor()?->encode() }}"
            data-has-more="{{ $items->hasMorePages() ? '1' : '0' }}"
        >
        <div class="d-flex flex-column gap-2" data-cursor-list>
            @foreach($items as $item)
                @include('cart.partials.cart-item-row', ['item' => $item, 'viewer' => $viewer, 'printboxRates' => $printboxRates])
            @endforeach
        </div>
            @include('partials.cursor-feed-footer')
        </div>

        <div class="mt-3 p-3 border rounded">
            @if((int) $creatorSubtotal > 0)
                <div class="d-flex justify-content-between mb-2">
                    <span>Creator Price</span>
                    <strong>Rp{{ number_format($creatorSubtotal, 0, ',', '.') }}</strong>
                </div>
            @endif
            @if((int) $applicationFee > 0)
                <div class="d-flex justify-content-between mb-2">
                    <span>Application Fee</span>
                    <strong>Rp{{ number_format($applicationFee, 0, ',', '.') }}</strong>
                </div>
            @endif
            @if((int) $printboxFee > 0)
                <div class="d-flex justify-content-between mb-2">
                    <span>Printbox sheets total</span>
                    <strong>Rp{{ number_format($printboxFee, 0, ',', '.') }}</strong>
                </div>
            @endif
            <div class="d-flex justify-content-between mb-2 border-top pt-2">
                <strong>Total Price</strong>
                <strong>Rp{{ number_format($total, 0, ',', '.') }}</strong>
            </div>
            <form method="POST" action="{{ route('cart.checkout') }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label">Payment Method</label>
                    <select class="form-select" name="payment_method" required>
                        <option>E-Wallet</option>
                        <option>QRIS</option>
                    </select>
                </div>
                <button class="tb-btn-primary" type="submit">Complete Checkout</button>
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

        function syncPrintboxOptions(form) {
            const toggle = form.querySelector('[data-cart-printbox-toggle]');
            const options = form.querySelector('[data-cart-printbox-options]');

            if (!toggle || !options) {
                return;
            }

            options.classList.toggle('d-none', !toggle.checked);
        }

        document.querySelectorAll('[data-cart-printbox-form]').forEach(syncPrintboxOptions);

        document.addEventListener('change', function (event) {
            const toggle = event.target.closest('[data-cart-printbox-toggle]');

            if (toggle) {
                const form = toggle.closest('[data-cart-printbox-form]');
                syncPrintboxOptions(form);
                form.requestSubmit();
                return;
            }

            const instantPrintboxInput = event.target.closest('[data-cart-printbox-autosubmit]');
            if (instantPrintboxInput) {
                instantPrintboxInput.closest('[data-cart-printbox-submit-form]')?.requestSubmit();
            }
        });

        const printboxTimers = new WeakMap();
        document.addEventListener('input', function (event) {
            const input = event.target.closest('[data-cart-printbox-autosubmit]');

            if (!input || input.type !== 'number') {
                return;
            }

            const form = input.closest('[data-cart-printbox-submit-form]');
            if (!form) {
                return;
            }

            window.clearTimeout(printboxTimers.get(form));
            printboxTimers.set(form, window.setTimeout(function () {
                form.requestSubmit();
            }, 500));
        });

    });
</script>
@include('partials.cursor-feed-script')
@endsection
