@extends('layouts.master')

@section('title', $artwork->name . ' - ConnectPrint')

@php
    use Illuminate\Support\Str;
    $loggedIn = session('user_id') !== null;
    $userSlug = $loggedIn ? Str::slug(session('name')) : null;
    $isOwner = $viewer && (int) $viewer->id === (int) $artwork->user_id;
    $alreadyPurchased = $viewer ? $viewer->hasPurchased($artwork) : false;
@endphp

@section('content')
<div class="tb-card p-4">
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="row g-4">
        <div class="col-md-5">
            <div class="ratio ratio-4x3">
                <img src="{{ $artwork->image_url }}" alt="{{ $artwork->name }}" class="w-100 h-100" style="object-fit:cover;">
            </div>
        </div>
        <div class="col-md-7">
            <div class="d-flex gap-1 flex-wrap mb-2">
                <span class="badge text-bg-warning">{{ $artwork->category->name ?? 'Uncategorized' }}</span>
                <span class="badge {{ $artwork->is_printable ? 'text-bg-primary' : 'text-bg-secondary' }}">{{ $artwork->is_printable ? 'Printable' : 'Display only' }}</span>
                @if($isOwner || ($viewer && $viewer->role === 'admin'))
                    <span class="badge text-bg-dark">{{ ucfirst($artwork->visibility) }}</span>
                    <span class="badge text-bg-info">{{ ucfirst($artwork->moderation_status) }}</span>
                @endif
            </div>

            <h1 style="font-size:1.6rem;font-weight:700;">{{ $artwork->name }}</h1>
            <p class="mb-1">Creator: <a href="{{ route('creators.show', $artwork->user_id ?: 1) }}" style="color:var(--tb-blue);">{{ $artwork->creatorName() }}</a></p>
            <p style="font-size:1.1rem;font-weight:700;color:var(--tb-blue);">Rp{{ number_format($artwork->price, 0, ',', '.') }}</p>
            <p style="white-space:pre-line;color:var(--tb-gray-text);">{{ $artwork->description ?: 'No description available.' }}</p>

            @if($artwork->tags->isNotEmpty())
                <div class="d-flex gap-1 flex-wrap mb-3">
                    @foreach($artwork->tags as $tag)
                        <span class="badge text-bg-light">#{{ $tag->name }}</span>
                    @endforeach
                </div>
            @endif

            <p class="small text-muted">Printbox charges its own application fee separately when you submit the file through Printbox.</p>

            <div class="d-flex gap-2 flex-wrap">
                @if($isOwner || $alreadyPurchased || ($viewer && $viewer->role === 'admin'))
                    <a class="tb-btn-primary" href="{{ route('artworks.print-file', ['username' => $userSlug ?? Str::slug($artwork->creatorName()), 'artwork' => $artwork->id]) }}">Open print-ready file</a>
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('printbox') }}">Printbox instructions</a>
                @elseif(! $artwork->is_printable)
                    <button class="btn btn-secondary btn-sm" disabled>Display only - printing is not permitted</button>
                @elseif($canPurchase)
                    <form method="POST" action="{{ route('cart.add', ['username' => $userSlug, 'artwork' => $artwork->id]) }}">
                        @csrf
                        <button class="tb-btn-primary" type="submit">Add printable access to cart</button>
                    </form>
                @elseif($loggedIn)
                    <button class="btn btn-secondary btn-sm" disabled>Not available for purchase</button>
                @else
                    <a class="tb-btn-primary" href="{{ route('login') }}">Login to purchase</a>
                @endif

                @if($loggedIn && ! $isOwner)
                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#reportForm">Report</button>
                @endif
            </div>

            <div id="reportForm" class="collapse mt-3">
                <form method="POST" action="{{ route('artworks.reports.store', ['artwork' => $artwork->id]) }}" class="border rounded p-3">
                    @csrf
                    <select class="form-select mb-2" name="reason" required>
                        <option value="copyright">Copyright</option>
                        <option value="inappropriate">Inappropriate</option>
                        <option value="spam">Spam</option>
                        <option value="other">Other</option>
                    </select>
                    <textarea class="form-control mb-2" name="details" rows="2" placeholder="Details"></textarea>
                    <button class="btn btn-danger btn-sm" type="submit">Submit report</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
