@extends('layouts.master')

@section('title', 'Profile - ConnectPrint')

@php
    use Illuminate\Support\Str;

    /** @var \App\Models\User $user */
    $isAdmin = session('role') === 'admin';
    $userSlug = $user->slug;
@endphp

@section('content')

<style>
    .cp-profile-header {
        display: grid;
        grid-template-columns: 96px minmax(0, 1fr);
        gap: 1.25rem;
        align-items: center;
    }

    .cp-avatar {
        width: 96px;
        height: 96px;
        border-radius: 999px;
        overflow: hidden;
        border: 2px solid #e5e7eb;
        background: #f3f4f6;
    }

    .cp-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .cp-stat {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.9rem;
        background: #ffffff;
    }

    .cp-stat strong {
        display: block;
        font-size: 1.4rem;
        line-height: 1;
    }

    .cp-action-row,
    .cp-card-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .cp-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .cp-artwork-thumb {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
    }

    .cp-list-link {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.85rem;
        color: inherit;
        text-decoration: none;
    }

    .cp-list-link:hover {
        border-color: var(--tb-blue);
    }

    .tb-btn-account {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 0.6rem 0.9rem;
        font-size: 0.9rem;
        font-weight: 500;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: filter 0.15s ease, background-color 0.15s ease;
    }

    .tb-btn-account-edit {
        background: var(--tb-blue);
        color: #ffffff;
    }

    .tb-btn-account-delete {
        background: #dc2626;
        color: #ffffff;
    }

    .tb-btn-account-logout {
        background: #b91c1c;
        color: #ffffff;
    }

    .tb-btn-account-role {
        background: #1d4ed8;
        color: #ffffff;
    }

    .tb-btn-account:hover {
        color: #ffffff;
        filter: brightness(1.08);
    }

    @media (max-width: 900px) {
        .cp-stat-grid,
        .cp-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .cp-profile-header,
        .cp-stat-grid,
        .cp-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger py-2">{{ session('error') }}</div>
@endif

<div class="tb-card p-4 p-md-5 mb-3">
    <div class="cp-profile-header">
        <div class="cp-avatar">
            <img src="{{ $user->profile_image_url }}" alt="Profile picture" class="w-100 h-100" style="object-fit:cover;">
        </div>

        <div>
            <p class="text-muted mb-1">Profile</p>
            <h1 style="font-size:1.8rem;font-weight:700;margin-bottom:0.2rem;">{{ $user->name }}</h1>
            <p style="margin:0;font-size:0.95rem;color:var(--tb-gray-text);">{{ $user->email }}</p>
            <div class="cp-action-row mt-3">
                <a class="tb-btn-primary" href="{{ route('artworks.create', ['username' => $userSlug]) }}">Upload image</a>
                <a class="tb-btn-secondary" href="{{ route('artworks.index', ['username' => $userSlug]) }}">Own images</a>
                <a class="tb-btn-secondary" href="{{ route('purchases.index', ['username' => $userSlug]) }}">Transaction history</a>
                <a class="tb-btn-secondary" href="{{ route('purchases.library', ['username' => $userSlug]) }}">Bought print files</a>
            </div>
        </div>
    </div>
</div>

<div class="cp-stat-grid mb-3">
    <div class="cp-stat">
        <span class="text-muted small">Own images</span>
        <strong>{{ $artworkCount }}</strong>
    </div>
    <div class="cp-stat">
        <span class="text-muted small">Public approved</span>
        <strong>{{ $publicArtworkCount }}</strong>
    </div>
    <div class="cp-stat">
        <span class="text-muted small">Purchases</span>
        <strong>{{ $purchaseCount }}</strong>
    </div>
    <div class="cp-stat">
        <span class="text-muted small">Sales</span>
        <strong>{{ $saleCount }}</strong>
    </div>
</div>

<div class="tb-card p-4 mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 style="font-size:1.25rem;font-weight:700;margin:0;">Own images</h2>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('artworks.index', ['username' => $userSlug]) }}">Manage all</a>
    </div>

    @if($recentArtworks->isEmpty())
        <div class="border rounded p-4 text-center">
            <p class="text-muted mb-3">No uploaded images yet.</p>
            <a class="tb-btn-primary" href="{{ route('artworks.create', ['username' => $userSlug]) }}">Upload image</a>
        </div>
    @else
        <div class="cp-grid">
            @foreach($recentArtworks as $artwork)
                <div class="border rounded p-3">
                    <img src="{{ $artwork->image_url }}" alt="{{ $artwork->name }}" class="cp-artwork-thumb mb-2">
                    <h3 style="font-size:1rem;font-weight:700;margin-bottom:0.35rem;">{{ $artwork->name }}</h3>
                    <div class="d-flex gap-1 flex-wrap mb-2">
                        <span class="badge text-bg-dark">{{ ucfirst($artwork->visibility) }}</span>
                        <span class="badge text-bg-info">{{ ucfirst($artwork->moderation_status) }}</span>
                        <span class="badge {{ $artwork->is_printable ? 'text-bg-primary' : 'text-bg-secondary' }}">
                            {{ $artwork->is_printable ? 'Printable' : 'Display only' }}
                        </span>
                    </div>
                    <div class="cp-card-actions">
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('artworks.show.user', ['username' => $userSlug, 'id' => $artwork->id]) }}">View</a>
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('artworks.edit', ['username' => $userSlug, 'artwork' => $artwork->id]) }}">Edit</a>
                        @if($artwork->original_path)
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('artworks.print-file', ['username' => $userSlug, 'artwork' => $artwork->id]) }}">File</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="tb-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h2 style="font-size:1.25rem;font-weight:700;margin:0;">Transaction history</h2>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('purchases.index', ['username' => $userSlug]) }}">View all</a>
            </div>

            @forelse($recentPurchases as $purchase)
                <a class="cp-list-link mb-2" href="{{ route('purchases.show', ['username' => $userSlug, 'purchase' => $purchase->id]) }}">
                    <span>
                        <strong>{{ $purchase->purchase_number }}</strong>
                        <span class="d-block text-muted small">{{ $purchase->items->count() }} image(s) - {{ ucfirst($purchase->payment_status) }}</span>
                    </span>
                    <strong>Rp{{ number_format($purchase->total, 0, ',', '.') }}</strong>
                </a>
            @empty
                <p class="text-muted mb-0">No purchase records yet.</p>
            @endforelse
        </div>
    </div>

    <div class="col-lg-6">
        <div class="tb-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h2 style="font-size:1.25rem;font-weight:700;margin:0;">Bought print files</h2>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('purchases.library', ['username' => $userSlug]) }}">View library</a>
            </div>

            @forelse($recentPurchasedItems as $item)
                <div class="border rounded p-3 mb-2">
                    <strong>{{ $item->artwork_title_snapshot }}</strong>
                    <div class="text-muted small">Creator: {{ $item->creator_name_snapshot }}</div>
                    <a class="btn btn-outline-primary btn-sm mt-2" href="{{ route('artworks.print-file', ['username' => $userSlug, 'artwork' => $item->product_id]) }}">Open file</a>
                </div>
            @empty
                <p class="text-muted mb-0">No bought print files yet.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="tb-card p-4 mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 style="font-size:1.25rem;font-weight:700;margin:0;">Sales records</h2>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('sales', ['username' => $userSlug]) }}">View all</a>
    </div>

    @forelse($recentSales as $item)
        <div class="cp-list-link mb-2">
            <span>
                <strong>{{ $item->artwork_title_snapshot }}</strong>
                <span class="d-block text-muted small">
                    Buyer: {{ $item->purchase->user->name ?? 'Unknown' }}
                    @if($item->purchase)
                        - {{ $item->purchase->purchase_number }}
                    @endif
                </span>
            </span>
            <strong>Rp{{ number_format($item->creator_price, 0, ',', '.') }}</strong>
        </div>
    @empty
        <p class="text-muted mb-0">No sales records yet.</p>
    @endforelse
</div>

<div class="tb-card p-4 mb-3">
    <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:1rem;">Account settings</h2>

    <div class="cp-action-row mb-3">
        <button type="button" class="tb-btn-account tb-btn-account-edit" id="btnToggleEdit">Edit account</button>
        <button type="button" class="tb-btn-account tb-btn-account-delete" id="btnToggleDelete">Delete account</button>
        <a href="{{ route('logout') }}" class="tb-btn-account tb-btn-account-logout">Sign out</a>

        @if($isAdmin)
            @if($isAdminPage)
                <a href="{{ route('home.user', ['username' => $userSlug]) }}" class="tb-btn-account tb-btn-account-role">User mode</a>
            @else
                <a href="{{ route('admin.user', ['username' => $userSlug]) }}" class="tb-btn-account tb-btn-account-role">Admin mode</a>
            @endif
        @endif
    </div>

    @php
        $updateRoute = $isAdminPage
            ? route('account.admin.update', ['username' => $userSlug])
            : route('account.update', ['username' => $userSlug]);

        $deleteRoute = $isAdminPage
            ? route('account.admin.delete', ['username' => $userSlug])
            : route('account.delete', ['username' => $userSlug]);
    @endphp

    <div id="editFormWrapper" class="d-none" style="max-width:420px;">
        <form method="POST" action="{{ $updateRoute }}">
            @csrf

            <div class="mb-2">
                <label for="name" class="form-label">Name</label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="mb-2">
                <label for="email" class="form-label">Email address</label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
            </div>

            <button type="submit" class="tb-btn-account tb-btn-account-edit">Save changes</button>
        </form>
    </div>

    <div id="deleteFormWrapper" class="d-none mt-3" style="max-width:420px;">
        <form method="POST" action="{{ $deleteRoute }}" onsubmit="return confirm('Are you sure you want to delete your account?');">
            @csrf

            <div class="mb-2">
                <label for="delete_password_confirmation" class="form-label">Confirm password</label>
                <input type="password" id="delete_password_confirmation" name="password_confirmation" class="form-control" required>
            </div>

            <button type="submit" class="tb-btn-account tb-btn-account-delete">Confirm delete</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editBtn = document.getElementById('btnToggleEdit');
        const editWrap = document.getElementById('editFormWrapper');
        const delBtn = document.getElementById('btnToggleDelete');
        const delWrap = document.getElementById('deleteFormWrapper');

        if (editBtn && editWrap) {
            editBtn.addEventListener('click', function () {
                editWrap.classList.toggle('d-none');
            });
        }

        if (delBtn && delWrap) {
            delBtn.addEventListener('click', function () {
                delWrap.classList.toggle('d-none');
            });
        }
    });
</script>

@endsection
