@extends('layouts.master')

@section('title', 'Creator sales - ConnectPrint')

@section('content')
<div class="tb-card p-4">
    <h1 style="font-size:1.4rem;font-weight:700;">Creator sales</h1>
    <div class="alert alert-info">This is simulated marketplace revenue. No real payment or payout occurred.</div>
    @forelse($items as $item)
        <div class="d-flex justify-content-between border rounded p-3 mb-2">
            <span>{{ $item->artwork_title_snapshot }}<br><small class="text-muted">Buyer: {{ $item->purchase->user->name ?? 'Unknown' }} - {{ $item->purchase->purchase_number }}</small></span>
            <strong>Rp{{ number_format($item->creator_price, 0, ',', '.') }}</strong>
        </div>
    @empty
        <p class="text-muted">No simulated sales yet.</p>
    @endforelse
    {{ $items->links() }}
</div>
@endsection
