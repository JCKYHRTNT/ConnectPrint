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
                <strong>Rp{{ number_format($item->creator_price, 0, ',', '.') }}</strong>
            </div>
            <div class="text-muted small">Creator: {{ $item->creator_name_snapshot }}</div>
            @if($item->artwork && $item->artwork->canDownloadFileBy($viewer))
                <a class="btn btn-outline-primary btn-sm mt-2" href="{{ route('artworks.print-file', ['artwork' => $item->product_id]) }}">Open print-ready file</a>
            @endif
        </div>
    @endforeach
    <a class="btn btn-outline-primary btn-sm" href="{{ route('printbox') }}">Print with Printbox instructions</a>
</div>
@endsection
