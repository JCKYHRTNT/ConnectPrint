@extends('layouts.master')

@section('title', $purchase->purchase_number . ' - ConnectPrint')

@section('content')
<div class="tb-card p-4">
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    <h1 style="font-size:1.4rem;font-weight:700;">{{ $purchase->purchase_number }}</h1>
    <p><span class="badge text-bg-success">{{ $purchase->payment_status }}</span> <span class="badge text-bg-info">Simulation</span></p>
    @foreach($purchase->items as $item)
        <div class="border rounded p-3 mb-2">
            <div class="d-flex justify-content-between">
                <strong>{{ $item->artwork_title_snapshot }}</strong>
                @if((int) $item->creator_price > 0)
                    <strong>Rp{{ number_format($item->creator_price, 0, ',', '.') }}</strong>
                @endif
            </div>
            <div class="text-muted small">Creator: {{ $item->creator_name_snapshot }}</div>
            @if((int) $item->creator_price > 0)
                <div class="text-muted small">Creator price: Rp{{ number_format($item->creator_price, 0, ',', '.') }}</div>
            @endif
            @if((int) ($item->printbox_fee ?? 0) > 0)
                <div class="text-muted small">
                    Printbox: {{ $item->printbox_mode === 'color' ? 'Color / Partial Color' : 'Black and White' }}
                    - {{ $item->printbox_sheet_count ?: 1 }} sheet(s)
                </div>
                <div class="text-muted small">Printbox sheets total: Rp{{ number_format($item->printbox_fee, 0, ',', '.') }}</div>
            @endif
            @if($item->artwork && $item->artwork->canDownloadFileBy($viewer))
                <a class="btn btn-outline-secondary btn-sm mt-2" href="{{ route('artworks.show', ['id' => $item->product_id]) }}">View artwork</a>
                <a class="btn btn-outline-primary btn-sm mt-2" href="{{ route('artworks.print-file', ['artwork' => $item->product_id]) }}">Open print-ready file</a>
            @endif
        </div>
    @endforeach
    <div class="border rounded p-3 mb-3">
        @if((int) ($purchase->subtotal ?? 0) > 0)
            <div class="d-flex justify-content-between mb-2">
                <span>Creator price</span>
                <strong>Rp{{ number_format($purchase->subtotal, 0, ',', '.') }}</strong>
            </div>
        @endif
        @if((int) ($purchase->application_fee ?? 0) > 0)
            <div class="d-flex justify-content-between mb-2">
                <span>Application fee</span>
                <strong>Rp{{ number_format($purchase->application_fee, 0, ',', '.') }}</strong>
            </div>
        @endif
        @if((int) ($purchase->printbox_fee ?? 0) > 0)
            <div class="d-flex justify-content-between mb-2">
                <span>Printbox sheets total</span>
                <strong>Rp{{ number_format($purchase->printbox_fee, 0, ',', '.') }}</strong>
            </div>
        @endif
        <div class="d-flex justify-content-between mb-2 border-top pt-2">
            <strong>Total price</strong>
            <strong>Rp{{ number_format($purchase->total, 0, ',', '.') }}</strong>
        </div>
        <div class="d-flex justify-content-between">
            <span>Payment method</span>
            <strong>{{ $purchase->payment_method ?? 'Unknown' }}</strong>
        </div>
    </div>
</div>
@endsection
