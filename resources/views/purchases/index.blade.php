@extends('layouts.master')

@section('title', 'Purchases - ConnectPrint')

@php use Illuminate\Support\Str; @endphp

@section('content')
<div class="tb-card p-4">
    <h1 style="font-size:1.4rem;font-weight:700;">Purchase history</h1>
    @forelse($purchases as $purchase)
        <a class="d-flex justify-content-between border rounded p-3 mb-2" href="{{ route('purchases.show', ['username' => Str::slug(session('name')), 'purchase' => $purchase->id]) }}">
            <span>{{ $purchase->purchase_number }} - {{ $purchase->items->count() }} artwork(s)</span>
            <strong>Rp{{ number_format($purchase->total, 0, ',', '.') }}</strong>
        </a>
    @empty
        <p class="text-muted">No purchases yet.</p>
    @endforelse
    {{ $purchases->links() }}
</div>
@endsection
