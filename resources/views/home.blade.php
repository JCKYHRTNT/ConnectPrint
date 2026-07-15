@extends('layouts.master')

@section('title', 'ConnectPrint - Artwork Marketplace')

@php
    use Illuminate\Support\Str;
    $userId = session('user_id');
    $userSlug = $userId ? Str::slug(session('name')) : null;
@endphp

@section('content')
<section class="mb-4">
    <div class="tb-card p-4" style="background:#0f172a;color:#fff;border:none;">
        <span class="badge rounded-pill" style="background:#facc15;color:#111827;">CONNECTPRINT MARKETPLACE</span>
        <h1 class="mt-2 mb-2" style="font-size:1.7rem;font-weight:700;">Browse printable artwork</h1>
        <p class="mb-0" style="color:#d1d5db;">Purchase creator-priced print-ready files here, then submit them manually to Printbox. Printbox fees and QR codes are handled by Printbox.</p>
    </div>
</section>

<section class="tb-card p-3 mb-3">
    <form method="GET" action="{{ $userId ? route('home.user', ['username' => $userSlug]) : route('home') }}" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Search</label>
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Title, creator, or tag">
        </div>
        <div class="col-md-3">
            <label class="form-label">Category</label>
            <select class="form-select" name="category">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Type</label>
            <select class="form-select" name="printable">
                <option value="">All</option>
                <option value="printable" @selected(request('printable') === 'printable')>Printable</option>
                <option value="display" @selected(request('printable') === 'display')>Display only</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Sort</label>
            <select class="form-select" name="sort">
                <option value="">Newest</option>
                <option value="price_asc" @selected(request('sort') === 'price_asc')>Price low</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>Price high</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="tb-btn-primary w-100" type="submit">Filter</button>
        </div>
    </form>
</section>

<section>
    @if($artworks->isEmpty())
        <div class="tb-card p-4">No approved public artwork found.</div>
    @else
        <div class="row g-3">
            @foreach($artworks as $artwork)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="tb-card overflow-hidden h-100">
                        <a href="{{ $userId ? route('artworks.show.user', ['username' => $userSlug, 'id' => $artwork->id]) : route('artworks.show', $artwork->id) }}" class="ratio ratio-4x3 d-block">
                            <img src="{{ $artwork->image_url }}" alt="{{ $artwork->name }}" class="w-100 h-100" style="object-fit:cover;">
                        </a>
                        <div class="p-3">
                            <div class="d-flex gap-1 flex-wrap mb-2">
                                <span class="badge text-bg-warning">{{ $artwork->category->name ?? 'Uncategorized' }}</span>
                                <span class="badge {{ $artwork->is_printable ? 'text-bg-primary' : 'text-bg-secondary' }}">{{ $artwork->is_printable ? 'Printable' : 'Display only' }}</span>
                            </div>
                            <h3 class="mb-1" style="font-size:1rem;font-weight:700;">{{ $artwork->name }}</h3>
                            <a href="{{ route('creators.show', $artwork->user_id ?: 1) }}" style="font-size:0.85rem;color:var(--tb-gray-text);">by {{ $artwork->creatorName() }}</a>
                            <div class="mt-2" style="font-weight:700;color:var(--tb-blue);">Rp{{ number_format($artwork->price, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-3">{{ $artworks->links() }}</div>
    @endif
</section>
@endsection
