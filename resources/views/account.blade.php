@extends('layouts.master')

@section('title', 'Profile - ConnectPrint')

@php
    /** @var \App\Models\User $user */
    $isAdmin = session('role') === 'admin';
    $userSlug = $user->slug;

    $profileTabs = [
        'account' => 'Account',
        'images' => 'Your Images',
        'history' => 'Transaction History',
    ];
    $imagesCursorEndpoint = ($isAdminPage
        ? route('account.admin', ['username' => $userSlug])
        : route('account')) . '?tab=images';
@endphp

@section('content')

<style>
    .cp-profile-nav {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        width: fit-content;
        max-width: 100%;
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 0.7rem;
        background: #ffffff;
        padding: 0.25rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }

    .cp-profile-nav-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.2rem;
        padding: 0.5rem 0.85rem;
        border-radius: 0.5rem;
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 500;
        line-height: 1;
        white-space: nowrap;
    }

    .cp-profile-nav-link:hover {
        color: #0f172a;
        background: #f8fafc;
    }

    .cp-profile-nav-link.is-active {
        color: #0f172a;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(226, 232, 240, 0.9);
    }

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

    .cp-stat,
    .cp-panel {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #ffffff;
    }

    .cp-stat {
        padding: 0.9rem;
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

    .cp-artwork-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        background: #ffffff;
    }

    .cp-list-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.85rem;
        color: inherit;
        text-decoration: none;
        background: #ffffff;
    }

    .cp-list-row:hover {
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

        .cp-profile-nav {
            width: 100%;
        }
    }
</style>

@if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger py-2">{{ session('error') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
    <div>
        <h1 style="font-size:1.65rem;font-weight:700;margin:0;">Profile</h1>
    </div>

    <nav class="cp-profile-nav" aria-label="Profile navigation">
        @foreach($profileTabs as $tabKey => $tabLabel)
            <a
                href="{{ route('account', ['tab' => $tabKey]) }}"
                class="cp-profile-nav-link {{ $activeTab === $tabKey ? 'is-active' : '' }}"
                @if($activeTab === $tabKey) aria-current="page" @endif
            >
                {{ $tabLabel }}
            </a>
        @endforeach
    </nav>
</div>

@if($activeTab === 'account')
    <div class="tb-card p-4 p-md-5 mb-3">
        <div class="cp-profile-header">
            <div class="cp-avatar">
                <img src="{{ $user->profile_image_url }}" alt="Profile picture" class="w-100 h-100" style="object-fit:cover;">
            </div>

            <div>
                <p class="text-muted mb-1">Account</p>
                <h2 style="font-size:1.45rem;font-weight:700;margin-bottom:0.2rem;">{{ $user->name }}</h2>
                <p style="margin:0;font-size:0.95rem;color:var(--tb-gray-text);">{{ $user->email }}</p>
            </div>
        </div>
    </div>

    <div class="cp-stat-grid mb-3">
        <div class="cp-stat">
            <span class="text-muted small">Your images</span>
            <strong>{{ $artworkCount }}</strong>
        </div>
        <div class="cp-stat">
            <span class="text-muted small">Public images</span>
            <strong>{{ $publicArtworkCount }}</strong>
        </div>
        <div class="cp-stat">
            <span class="text-muted small">Bought images</span>
            <strong>{{ $purchaseCount }}</strong>
        </div>
        <div class="cp-stat">
            <span class="text-muted small">Images sold</span>
            <strong>{{ $saleCount }}</strong>
        </div>
    </div>

    <div class="tb-card p-4 mb-3">
        <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:1rem;">Account Settings</h2>

        <div class="cp-action-row mb-3">
            <button type="button" class="tb-btn-account tb-btn-account-edit" id="btnToggleEdit">Edit account</button>
            <button type="button" class="tb-btn-account tb-btn-account-delete" id="btnToggleDelete">Delete account</button>
            <a href="{{ route('logout') }}" class="tb-btn-account tb-btn-account-logout">Sign out</a>

            @if($isAdmin)
                @if($isAdminPage)
                    <a href="{{ route('home.user') }}" class="tb-btn-account tb-btn-account-role">User mode</a>
                @else
                    <a href="{{ route('admin.user', ['username' => $userSlug]) }}" class="tb-btn-account tb-btn-account-role">Admin mode</a>
                @endif
            @endif
        </div>

        @php
            $updateRoute = $isAdminPage
                ? route('account.admin.update', ['username' => $userSlug])
                : route('account.update');

            $deleteRoute = $isAdminPage
                ? route('account.admin.delete', ['username' => $userSlug])
                : route('account.delete');
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
@elseif($activeTab === 'images')
    <div class="tb-card p-4 mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h2 style="font-size:1.25rem;font-weight:700;margin:0;">Your Images</h2>
            </div>
            <a class="tb-btn-primary" href="{{ route('artworks.create') }}">Upload Image</a>
        </div>

        @if($artworks->count() === 0)
            <div class="border rounded p-4 text-center">
                <p class="text-muted mb-3">No images uploaded yet.</p>
                <a class="tb-btn-primary" href="{{ route('artworks.create') }}">Upload Image</a>
            </div>
        @else
            <div
                data-cursor-feed
                data-cursor-endpoint="{{ $imagesCursorEndpoint }}"
                data-next-cursor="{{ $artworks->nextCursor()?->encode() }}"
                data-has-more="{{ $artworks->hasMorePages() ? '1' : '0' }}"
            >
                <div class="cp-grid" data-cursor-list>
                    @foreach($artworks as $artwork)
                        @include('artworks.partials.profile-image-card', ['artwork' => $artwork])
                    @endforeach
                </div>
                @include('partials.cursor-feed-footer')
            </div>
        @endif
    </div>
@else
    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="tb-card p-4 h-100">
                <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:1rem;">Images Sold</h2>

                @forelse($sales as $item)
                    <div class="cp-list-row mb-2">
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
                    <p class="text-muted mb-0">No sold images yet.</p>
                @endforelse
            </div>
        </div>

        <div class="col-lg-6">
            <div class="tb-card p-4 h-100">
                <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:1rem;">
                    <a href="{{ route('purchases.library') }}" style="color:inherit;text-decoration:none;">Images Bought</a>
                </h2>

                @forelse($purchasedItems as $item)
                    <div class="cp-list-row mb-2">
                        <span>
                            <strong>{{ $item->artwork_title_snapshot }}</strong>
                            <span class="d-block text-muted small">Creator: {{ $item->creator_name_snapshot }}</span>
                        </span>
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('artworks.print-file', ['artwork' => $item->product_id]) }}">Open file</a>
                    </div>
                @empty
                    <p class="text-muted mb-0">No bought images yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="tb-card p-4 mb-3">
        <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:1rem;">Printing Done</h2>

        @forelse($purchasedItems as $item)
            <div class="cp-list-row mb-2">
                <span>
                    <strong>{{ $item->artwork_title_snapshot }}</strong>
                    <span class="d-block text-muted small">
                        {{ $item->purchase->purchase_number ?? 'Purchase record' }} - ready for Printbox handoff
                    </span>
                </span>
                <span class="text-muted small">{{ $item->purchase->created_at?->format('Y-m-d') }}</span>
            </div>
        @empty
            <p class="text-muted mb-0">No printing records yet.</p>
        @endforelse
    </div>
@endif

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

        document.addEventListener('click', function (event) {
            const copyButton = event.target.closest('[data-copy-artwork-link]');

            if (!copyButton) {
                return;
            }

            const link = copyButton.dataset.copyArtworkLink;
            const originalText = copyButton.textContent;

            function markCopied() {
                copyButton.textContent = 'Copied';
                window.setTimeout(function () {
                    copyButton.textContent = originalText;
                }, 1200);
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(link).then(markCopied);
                return;
            }

            const input = document.createElement('input');
            input.value = link;
            input.setAttribute('readonly', 'readonly');
            input.style.position = 'fixed';
            input.style.opacity = '0';
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            input.remove();
            markCopied();
        });
    });
</script>

@if($activeTab === 'images')
    @include('partials.cursor-feed-script')
@endif

@endsection
