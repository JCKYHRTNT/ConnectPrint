@extends('layouts.master')

@section('title', 'Cart - ConnectPrint')

@section('content')
<div class="tb-card p-4">
    <h1 style="font-size:1.4rem;font-weight:700;">Printable access cart</h1>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @error('cart') <div class="alert alert-danger">{{ $message }}</div> @enderror

    @if($items->isEmpty())
        <p class="mb-0 text-muted">Your cart is empty.</p>
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
@endsection
