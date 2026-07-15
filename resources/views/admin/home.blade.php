@extends('layouts.master')

@section('title', 'ConnectPrint - Admin')

@php
    use Illuminate\Support\Str;

    $query      = request('q');
    $categoryId = request()->filled('category') ? (int) request('category') : null;

    // $categories is an Eloquent collection passed from AdminController
    $recentCategories = $categories
        ->sortBy('id')
        ->take(3)
        ->map(fn ($cat) => ['id' => $cat->id, 'name' => $cat->name])
        ->values()
        ->all();

    $categoryNames = $categories->pluck('name', 'id')->toArray();
    $isAllActive   = is_null($categoryId);
    $adminSlug     = Str::slug(session('name'));
@endphp

@section('content')

<style>
    /* EDIT BUTTON (blue) */
    .tb-btn-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--tb-blue);
        color: #ffffff;
        border-radius: 999px;
        padding: 0.4rem 0.9rem;
        font-size: 0.85rem;
        font-weight: 500;
        border: none;
        cursor: pointer;
        text-decoration: none;
    }
    .tb-btn-secondary:hover {
        filter: brightness(1.12);
    }

    /* DELETE BUTTON (red) */
    .tb-btn-danger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #dc2626;
        color: #ffffff;
        border-radius: 999px;
        padding: 0.4rem 0.9rem;
        font-size: 0.85rem;
        font-weight: 500;
        border: none;
        cursor: pointer;
    }
    .tb-btn-danger:hover {
        background: #ef4444;
    }

    .tb-artwork-card {
        min-height: 360px;
        display: flex;
        flex-direction: column;
    }

    .tb-add-artwork-card { cursor: pointer; }

    .tb-add-artwork-circle {
        width: 80px;
        height: 80px;
        border-radius: 999px;
        background: var(--tb-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 2rem;
        font-weight: 600;
    }
</style>

{{-- Intro --}}
<section class="mb-4">
    <div class="tb-card p-4 p-md-4" style="
        background: radial-gradient(circle at top left, var(--tb-yellow) 0, var(--tb-blue) 45%, var(--tb-black) 100%);
        color: #f9fafb;
        border: none;
    ">
        <div class="row align-items-end gy-3">
            <div class="col-md-8">
                <span class="badge rounded-pill" style="background:#facc15;color:#111827;font-size:0.7rem;letter-spacing:0.08em;">
                    ADMIN - ARTWORK MANAGEMENT
                </span>
                <h1 class="mt-2 mb-2" style="font-size:1.6rem;font-weight:600;">
                    ConnectPrint artwork management
                </h1>
            </div>
        </div>
    </div>
</section>

{{-- Category filter row --}}
<section class="mb-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2" style="gap:0.5rem;">
        <h2 class="mb-0" style="font-size:1rem;font-weight:600;">Categories</h2>

        <div class="d-flex flex-wrap align-items-center" style="gap:0.5rem;">

            {{-- All --}}
            <a
                href="{{ url('/a/'.$adminSlug).($query ? '?q='.urlencode($query) : '') }}"
                class="tb-pill-link"
                style="
                    background: {{ $isAllActive ? 'var(--tb-blue)' : 'rgba(15,23,42,0.06)' }};
                    color: {{ $isAllActive ? '#f9fafb' : '#111827' }};
                    font-size:0.8rem;
                    padding:0.4rem 0.9rem;
                "
            >
                All
            </a>

            {{-- Recent/default categories --}}
            @foreach($recentCategories as $cat)
                @php
                    $isActive = $categoryId === $cat['id'];
                    $catUrl   = url('/a/'.$adminSlug.'?category='.$cat['id'].($query ? '&q='.urlencode($query) : ''));
                @endphp

                <a
                    href="{{ $catUrl }}"
                    class="tb-pill-link"
                    style="
                        background: {{ $isActive ? 'var(--tb-blue)' : 'rgba(15,23,42,0.06)' }};
                        color: {{ $isActive ? '#f9fafb' : '#111827' }};
                        font-size:0.8rem;
                        padding:0.4rem 0.9rem;
                    "
                >
                    {{ ucfirst($cat['name']) }}
                </a>
            @endforeach

            {{-- + Category button --}}
            <button
                type="button"
                class="tb-pill-link"
                style="
                    background: rgba(15,23,42,0.06);
                    color:#111827;
                    font-size:0.9rem;
                    padding:0.35rem 0.85rem;
                    border-radius:999px;
                    border:1px dashed rgba(148,163,184,0.8);
                "
                data-bs-toggle="modal"
                data-bs-target="#modalCreateCategory"
            >
                +
            </button>
        </div>
    </div>

    @if(!is_null($categoryId) || $query)
        <div style="font-size:0.8rem;color:var(--tb-gray-text);">
            @if(!is_null($categoryId))
                <span>
                    Filter:
                    <strong>{{ ucfirst($categoryNames[$categoryId] ?? 'Unknown') }}</strong>
                </span>
            @endif

            @if(!is_null($categoryId) && $query)
                <span> · </span>
            @endif

            @if($query)
                <span>Search: <strong>{{ $query }}</strong></span>
            @endif
        </div>
    @endif
</section>

{{-- Artwork cards --}}
<section>
    <div class="row g-3">

        {{-- Add Artwork CARD --}}
        <div class="col-6 col-md-4 col-lg-3">
            <div class="tb-card tb-artwork-card overflow-hidden tb-add-artwork-card">
                <button type="button"
                        class="w-100 h-100 border-0 bg-transparent p-0 d-flex flex-column"
                        data-bs-toggle="modal"
                        data-bs-target="#modalAddArtwork">

                    {{-- top --}}
                    <div class="ratio ratio-4x3">
                        <div class="d-flex align-items-center justify-content-center w-100 h-100">
                            <div class="tb-add-artwork-circle">+</div>
                        </div>
                    </div>

                    {{-- bottom --}}
                    <div class="p-2 p-md-3 d-flex align-items-end justify-content-center">
                        <div style="font-weight:600;font-size:0.95rem;color:#111827;">
                            Add Artwork
                        </div>
                    </div>
                </button>
            </div>
        </div>

        {{-- ARTWORKS --}}
        @forelse($artworks as $artwork)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="tb-card tb-artwork-card overflow-hidden">

                    {{-- Clickable image --}}
                    <a href="{{ route('admin.artworks.show', [
                            'username' => $adminSlug,
                            'artwork' => $artwork->id,
                        ]) }}"
                       class="ratio ratio-4x3 d-block">
                        <img
                            src="{{ $artwork->image_url }}"
                            alt="{{ $artwork->name }}"
                            class="w-100 h-100"
                            style="object-fit:cover;"
                        >
                    </a>

                    <div class="p-2 p-md-3 d-flex flex-column h-100">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                @php
                                    $catId = $artwork->category_id;

                                    if (!is_null($catId)) {
                                        $catUrl   = url('/a/'.$adminSlug.'?category=' . $catId . ($query ? '&q=' . urlencode($query) : ''));
                                        $catLabel = ucfirst($categoryNames[$catId] ?? 'Unknown');
                                    } else {
                                        $catUrl   = null;
                                        $catLabel = 'Uncategorized';
                                    }
                                @endphp

                                @if(!is_null($catId))
                                    <a
                                        href="{{ $catUrl }}"
                                        class="badge rounded-pill"
                                        style="background:#facc15;color:#111827;font-size:0.7rem;text-decoration:none;cursor:pointer;"
                                    >
                                        {{ $catLabel }}
                                    </a>
                                @else
                                    <span
                                        class="badge rounded-pill"
                                        style="background:#9ca3af;color:#111827;font-size:0.7rem;"
                                    >
                                        {{ $catLabel }}
                                    </span>
                                @endif
                            </div>

                            {{-- Clickable name --}}
                            <h3 class="mb-1" style="font-size:0.9rem;font-weight:600;">
                                <a href="{{ route('admin.artworks.show', [
                                        'username' => $adminSlug,
                                        'artwork' => $artwork->id,
                                    ]) }}"
                                   style="color:inherit;text-decoration:none;">
                                    {{ $artwork->name }}
                                </a>
                            </h3>

                            {{-- Price --}}
                            <p class="mb-1" style="font-size:0.9rem;font-weight:600;color:var(--tb-blue);">
                                Rp{{ number_format($artwork->price, 0, ',', '.') }}
                            </p>

                            <p class="mb-2" style="font-size:0.8rem;color:#4b5563;">{{ $artwork->is_printable ? 'Printable access' : 'Display only' }} - {{ ucfirst($artwork->moderation_status) }}</p>
                        </div>

                        {{-- ADMIN ACTIONS --}}
                        <div class="d-flex gap-2 mt-auto">
                            <a href="{{ route('admin.artworks.edit', [
                                    'username' => $adminSlug,
                                    'artwork' => $artwork->id,
                                ]) }}"
                               class="tb-btn-secondary flex-fill text-center">
                                Edit
                            </a>

                            <form method="POST"
                                  action="{{ route('admin.artworks.destroy', [
                                        'username' => $adminSlug,
                                        'artwork' => $artwork->id,
                                  ]) }}"
                                  class="flex-fill m-0 p-0"
                                  onsubmit="return confirm('Delete this artwork?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tb-btn-danger w-100">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        @empty
        @endforelse
    </div>
</section>

<script>
    function tbShowNewArtworkForm() {
        document.getElementById('tb-new-artwork-closed').classList.add('d-none');
        document.getElementById('tb-new-artwork-open').classList.remove('d-none');
    }

    function tbHideNewArtworkForm() {
        document.getElementById('tb-new-artwork-open').classList.add('d-none');
        document.getElementById('tb-new-artwork-closed').classList.remove('d-none');
    }
</script>

{{-- Add Artwork MODAL --}}
<div class="modal fade" id="modalAddArtwork" tabindex="-1" aria-labelledby="modalAddArtworkLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddArtworkLabel">New Artwork</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('admin.artworks.store', ['username' => $adminSlug]) }}">
                @csrf
                <div class="modal-body">

                    <div class="mb-2">
                        <label class="form-label" for="np_name">Title</label>
                        <input type="text" id="np_name" name="name" class="form-control" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label" for="np_price">Price (Rp)</label>
                        <input type="number" id="np_price" name="price" class="form-control" min="0" required>
                    </div>

                    <input type="hidden" id="np_quantity" name="quantity" value="1">

                    <div class="mb-2">
                        <label class="form-label" for="np_category">Category</label>
                        <select id="np_category" name="category_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label" for="np_image">Image URL</label>
                        <input type="text" id="np_image" name="image" class="form-control">
                    </div>

                    <div class="mb-2">
                        <label class="form-label" for="np_description">Description</label>
                        <textarea id="np_description" name="description" rows="3" class="form-control"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="tb-btn-primary">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- CREATE CATEGORY MODAL --}}
<div class="modal fade" id="modalCreateCategory" tabindex="-1" aria-labelledby="modalCreateCategoryLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.categories.store', ['username' => $adminSlug]) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCreateCategoryLabel">New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label" for="cat_new_name">Name</label>
                        <input type="text" id="cat_new_name" name="name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="tb-btn-secondary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
