@extends('layouts.master')

@section('title', 'Purchased artwork - ConnectPrint')

@section('content')
<div class="tb-card p-4">
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
        <h1 class="mb-0" style="font-size:1.4rem;font-weight:700;">Images Bought</h1>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('account', ['tab' => 'history']) }}">Back to Transaction History</a>
    </div>

    @if($items->count() > 0)
        <div class="row g-3">
            @foreach($items as $item)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="tb-card overflow-hidden h-100">
                        @if($item->artwork)
                            <a href="{{ route('artworks.show', $item->product_id) }}" class="cp-artwork-frame cp-artwork-frame-card">
                                <img src="{{ $item->artwork->image_url }}" alt="{{ $item->artwork_title_snapshot }}" class="cp-artwork-image-contained">
                            </a>
                        @else
                            <div class="cp-artwork-frame cp-artwork-frame-card">
                                <img src="{{ asset('images/placeholders/blank-artwork.svg') }}" alt="{{ $item->artwork_title_snapshot }}" class="cp-artwork-image-contained">
                            </div>
                        @endif

                        <div class="p-3">
                            <h2 class="mb-1" style="font-size:1rem;font-weight:700;">{{ $item->artwork_title_snapshot }}</h2>
                            <div class="text-muted small mb-2">
                                Purchased {{ $item->purchase->created_at->format('Y-m-d') }} for Rp{{ number_format($item->creator_price, 0, ',', '.') }}
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                @if($item->artwork && $item->artwork->canDownloadFileBy($viewer))
                                    <a class="btn btn-outline-primary btn-sm" href="{{ route('artworks.print-file', ['artwork' => $item->product_id]) }}">Open print-ready file</a>
                                @endif
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('printbox') }}">Printbox instructions</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-muted">No purchased artwork yet.</p>
    @endif

    <div class="mt-3">{{ $items->links() }}</div>
</div>
@endsection
