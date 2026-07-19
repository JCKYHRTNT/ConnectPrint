@extends('layouts.master')

@section('title', $artwork->name . ' - Admin - ConnectPrint')

@php
    use Illuminate\Support\Str;
    $adminSlug = Str::slug(session('name'));
@endphp

@section('content')

<style>
    .tb-btn-secondary {
        display:inline-flex;align-items:center;justify-content:center;
        background:var(--tb-blue);color:#fff;border-radius:999px;
        padding:0.4rem 0.9rem;font-size:0.85rem;font-weight:500;
        border:none;cursor:pointer;text-decoration:none;
    }
    .tb-btn-secondary:hover { filter:brightness(1.12); }

    .tb-btn-danger {
        display:inline-flex;align-items:center;justify-content:center;
        background:#dc2626;color:#fff;border-radius:999px;
        padding:0.4rem 0.9rem;font-size:0.85rem;font-weight:500;
        border:none;cursor:pointer;text-decoration:none;
    }
    .tb-btn-danger:hover { background:#ef4444; }

    .tb-btn-ghost {
        display:inline-flex;align-items:center;justify-content:center;
        gap:0.35rem;
        background:#9ca3af;color:#ffffff;border-radius:999px;
        padding:0.4rem 0.9rem;font-size:0.85rem;font-weight:500;
        text-decoration:none;border:none;cursor:pointer;
        transition:background 0.2s ease;
    }
    .tb-btn-ghost:hover { background:#000000; }
</style>

<div class="tb-card p-4">
    <div class="row g-3">

        {{-- IMAGE --}}
        <div class="col-md-5">
            <div class="ratio ratio-4x3">
                <img
                    src="{{ $artwork->image_url }}"
                    alt="{{ $artwork->name }}"
                    class="w-100 h-100"
                    style="object-fit:cover;"
                >
            </div>
        </div>

        {{-- DETAILS + EDIT FORM --}}
        <div class="col-md-7">

            {{-- CATEGORY --}}
            @if($artwork->category)
                <span class="badge rounded-pill"
                      style="background:#facc15;color:#111827;font-size:0.75rem;">
                    {{ strtoupper($artwork->category->name) }}
                </span>
            @else
                <span class="badge rounded-pill"
                      style="background:#9ca3af;color:#111827;font-size:0.75rem;">
                    UNCATEGORIZED
                </span>
            @endif

            {{-- NAME --}}
            <h1 class="mt-2 mb-2" style="font-size:1.4rem;font-weight:600;">
                {{ $artwork->name }}
            </h1>

            {{-- PRICE --}}
            <p style="font-size:1rem;font-weight:600;color:var(--tb-blue);margin-bottom:0.25rem;">
                Rp{{ number_format($artwork->price, 0, ',', '.') }}
            </p>

            <p style="font-size:0.9rem;font-weight:500;color:#111827;margin-bottom:0.25rem;">
                {{ $artwork->is_printable ? 'Printable access' : 'Display only' }}
                @if(in_array($artwork->moderation_status, ['draft', 'rejected'], true))
                    - {{ ucfirst($artwork->moderation_status) }}
                @endif
            </p>

            {{-- DESCRIPTION --}}
            <p style="font-size:0.9rem;color:var(--tb-gray-text);  white-space:pre-line;">
                {{ $artwork->description ?? 'No description available.' }}
            </p>

            <hr class="my-3">
            {{-- EDIT FORM --}}
            <h2 style="font-size:1rem;font-weight:600;margin-bottom:0.75rem;">Edit Artwork</h2>

            <form method="POST"
                  action="{{ route('admin.artworks.update', ['username' => $adminSlug, 'artwork' => $artwork->id]) }}">
                @csrf
                @method('PUT')

                <div class="mb-2">
                    <label class="form-label" for="name">Name</label>
                    <input type="text"
                           id="name"
                           name="name"
                           class="form-control"
                           value="{{ old('name', $artwork->name) }}"
                           required>
                </div>

                <div class="mb-2">
                    <label class="form-label" for="price">Price (Rp)</label>
                    <input type="number"
                           id="price"
                           name="price"
                           class="form-control"
                           min="0"
                           value="{{ old('price', $artwork->price) }}"
                           required>
                </div>

                <div class="mb-2">
                    <label class="form-label" for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-select">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}"
                                {{ (old('category_id', $artwork->category_id) == $cat->id) ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label" for="image">Image URL</label>
                    <input type="text"
                           id="image"
                           name="image"
                           class="form-control"
                           value="{{ old('image', $artwork->image) }}">
                </div>

                <input type="hidden" name="quantity" value="1">

                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description"
                              name="description"
                              rows="3"
                              class="form-control">{{ old('description', $artwork->description) }}</textarea>
                </div>

                <button type="submit" class="tb-btn-secondary">
                    Save Changes
                </button>
            </form>

            {{-- DELETE --}}
            <form method="POST"
                  action="{{ route('admin.artworks.destroy', ['username' => $adminSlug, 'artwork' => $artwork->id]) }}"
                  class="mt-3"
                  onsubmit="return confirm('Delete this artwork?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="tb-btn-danger">
                    Delete Artwork
                </button>
            </form>

            {{-- BACK TO ADMIN HOME --}}
            <a href="{{ route('admin.user', ['username' => $adminSlug]) }}"
               class="tb-btn-ghost mt-3">
                <img
                    src="{{ asset('images/home_icon.png') }}"
                    alt="Admin Home"
                    style="height:16px;width:16px;opacity:0.85;">
                Admin Home
            </a>
        </div>
    </div>
</div>

@endsection
