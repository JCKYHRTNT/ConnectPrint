@extends('layouts.master')

@section('title', $creator->name . ' - ConnectPrint Creator')

@section('content')
<div class="tb-card p-4 mb-3">
    <h1 style="font-size:1.5rem;font-weight:700;">{{ $creator->name }}</h1>
    <p class="mb-0 text-muted">Joined {{ $creator->created_at?->format('Y-m-d') }} - {{ $artworks->total() }} public approved artwork(s)</p>
</div>
<div class="row g-3">
    @forelse($artworks as $artwork)
        <div class="col-md-3">
            <a class="tb-card d-block overflow-hidden h-100" href="{{ route('artworks.show', $artwork->id) }}">
                <img src="{{ $artwork->image_url }}" alt="{{ $artwork->name }}" class="w-100" style="height:160px;object-fit:cover;">
                <div class="p-2"><strong>{{ $artwork->name }}</strong></div>
            </a>
        </div>
    @empty
        <div class="tb-card p-4">No public approved artwork.</div>
    @endforelse
</div>
<div class="mt-3">{{ $artworks->links() }}</div>
@endsection
