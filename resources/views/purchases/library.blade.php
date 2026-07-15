@extends('layouts.master')

@section('title', 'Purchased artwork - ConnectPrint')

@php use Illuminate\Support\Str; @endphp

@section('content')
<div class="tb-card p-4">
    <h1 style="font-size:1.4rem;font-weight:700;">Purchased artwork library</h1>
    @forelse($items as $item)
        <div class="border rounded p-3 mb-2">
            <strong>{{ $item->artwork_title_snapshot }}</strong>
            <div class="text-muted small">Purchased {{ $item->purchase->created_at->format('Y-m-d') }} for Rp{{ number_format($item->creator_price, 0, ',', '.') }}</div>
            @if($item->artwork)
                <a class="btn btn-outline-primary btn-sm mt-2" href="{{ route('artworks.print-file', ['username' => Str::slug(session('name')), 'artwork' => $item->artwork_id]) }}">Open print-ready file</a>
            @endif
            <a class="btn btn-outline-secondary btn-sm mt-2" href="{{ route('printbox') }}">Printbox instructions</a>
        </div>
    @empty
        <p class="text-muted">No purchased artwork yet.</p>
    @endforelse
    {{ $items->links() }}
</div>
@endsection
