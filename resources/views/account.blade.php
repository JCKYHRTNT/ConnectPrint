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
    $accountBaseRoute = $isAdminPage
        ? route('account.admin', ['username' => $userSlug])
        : route('account');
    $imagesFilterQuery = collect(request()->except('cursor', 'history'))
        ->merge(['tab' => 'images'])
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->all();
    $historyFilterQuery = collect(request()->except('cursor'))
        ->merge(['tab' => 'history', 'history' => $historyType ?? 'sold'])
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->all();
    $imagesCursorEndpoint = $accountBaseRoute . '?' . http_build_query($imagesFilterQuery);
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

    .cp-history-switcher {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.7rem;
        background: #ffffff;
        padding: 0.25rem;
    }

    .cp-history-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2rem;
        padding: 0.45rem 0.75rem;
        border-radius: 0.5rem;
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .cp-history-link.is-active {
        color: #0f172a;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(226, 232, 240, 0.9);
    }

    .cp-filter-panel {
        display: grid;
        grid-template-columns: minmax(220px, 1.4fr) minmax(180px, 1fr) minmax(150px, 0.8fr) minmax(150px, 0.8fr) minmax(130px, 0.55fr);
        gap: 0.75rem;
        align-items: end;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.85rem;
        background: #ffffff;
    }

    .cp-history-filter-panel {
        grid-template-columns: minmax(260px, 1fr) minmax(150px, 0.45fr) auto;
    }

    .cp-filter-actions {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
    }

    .cp-filter-label {
        margin-bottom: 0;
    }

    [data-cp-tag-inputs] {
        display: none;
    }

    .cp-filter-tags-row {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: minmax(220px, 1.4fr) minmax(180px, 1fr) minmax(150px, 0.8fr) minmax(150px, 0.8fr) minmax(130px, 0.55fr);
        gap: 0.75rem;
        align-items: start;
    }

    .cp-search-tag-box {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        height: 2.5rem;
        max-height: 2.5rem;
        overflow-x: auto;
        overflow-y: hidden;
        padding: 0.3rem 0.45rem;
        background: #ffffff;
    }

    .cp-search-tag-list {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.45rem;
        width: max-content;
        max-width: none;
    }

    .cp-search-tag-input {
        border: none;
        outline: none;
        min-width: 12rem;
        flex: 0 0 12rem;
        padding: 0.15rem;
    }

    .cp-search-tag-input.has-tags::placeholder {
        color: transparent;
    }

    .cp-tag-chip,
    .cp-suggested-tag {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #f8fafc;
        color: #64748b;
        padding: 0.15rem 0.45rem;
        font-size: 0.72rem;
        font-weight: 600;
    }

    .cp-tag-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .cp-tag-chip button {
        border: none;
        background: transparent;
        color: #64748b;
        font-weight: 700;
        line-height: 1;
        padding: 0;
    }

    .cp-suggested-tag-menu {
        max-height: 6.25rem;
        overflow-y: auto;
        width: min(32rem, 85vw);
    }

    .cp-suggested-tag-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }

    .cp-suggested-tag {
        display: inline-flex;
        width: auto;
        background: #ffffff;
    }

    .cp-suggested-tag.is-selected {
        border-color: var(--tb-blue);
        color: var(--tb-blue);
        background: #ecfdf5;
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

        .cp-filter-panel,
        .cp-history-filter-panel {
            grid-template-columns: 1fr;
        }

        .cp-filter-actions {
            justify-content: flex-start;
        }

        .cp-filter-tags-row {
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

        <form method="GET" action="{{ $accountBaseRoute }}" class="cp-filter-panel mb-3" data-autofilter-form>
            <input type="hidden" name="tab" value="images">
            <div data-cp-tag-inputs>
                @foreach(($selectedTags ?? []) as $tag)
                    <input type="hidden" name="tags[]" value="{{ $tag }}">
                @endforeach
            </div>
            <div>
                <label class="form-label cp-filter-label" for="imageSearch">Search</label>
            </div>
            <div>
                <label class="form-label cp-filter-label" for="imageCategory">Category</label>
            </div>
            <div>
                <label class="form-label cp-filter-label" for="imageVisibility">Visibility</label>
            </div>
            <div>
                <label class="form-label cp-filter-label" for="imagePrintable">Type</label>
            </div>
            <div>
                <label class="form-label cp-filter-label" for="imageSort">Sort</label>
            </div>
            <div>
                <div class="cp-search-tag-box">
                    <div class="cp-search-tag-list" data-cp-selected-tags aria-label="Search tags">
                        @foreach(($selectedTags ?? []) as $tag)
                            <span class="cp-tag-chip" data-cp-tag-chip="{{ $tag }}">#{{ $tag }} <button type="button" data-cp-remove-tag="{{ $tag }}">x</button></span>
                        @endforeach
                        <input class="cp-search-tag-input" id="imageSearch" name="q" value="{{ request('q') }}" placeholder="Search or type #tag and press Enter" data-autofilter-debounce data-cp-tag-text-input>
                    </div>
                </div>
            </div>
            <div>
                <select class="form-select" id="imageCategory" name="category" data-autofilter-instant>
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select class="form-select" id="imageVisibility" name="visibility" data-autofilter-instant>
                    <option value="">All</option>
                    <option value="public" @selected(request('visibility') === 'public')>Public</option>
                    <option value="unlisted" @selected(request('visibility') === 'unlisted')>Unlisted</option>
                    <option value="private" @selected(request('visibility') === 'private')>Private</option>
                    <option value="archived" @selected(request('visibility') === 'archived')>Archived</option>
                </select>
            </div>
            <div>
                <select class="form-select" id="imagePrintable" name="printable" data-autofilter-instant>
                    <option value="">All</option>
                    <option value="printable" @selected(request('printable') === 'printable')>Printable</option>
                    <option value="display" @selected(request('printable') === 'display')>Display only</option>
                </select>
            </div>
            <div>
                <select class="form-select" id="imageSort" name="sort" data-autofilter-instant>
                    <option value="">Newest</option>
                    <option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option>
                    <option value="price_asc" @selected(request('sort') === 'price_asc')>Price low</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>Price high</option>
                </select>
            </div>
            <div class="cp-filter-tags-row">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        Suggested tags
                    </button>
                    <div class="dropdown-menu p-2 cp-suggested-tag-menu" aria-label="Suggested tags">
                        <div class="cp-suggested-tag-list">
                            @foreach($suggestedTags as $tag)
                                <button class="cp-suggested-tag @if(in_array($tag, $selectedTags ?? [], true)) is-selected @endif" type="button" data-cp-tag-button data-tag="{{ $tag }}">#{{ $tag }} +</button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div></div>
                <div></div>
                <div></div>
                <div class="cp-filter-actions">
                    <a class="btn btn-outline-secondary btn-sm" href="{{ $accountBaseRoute }}?tab=images">Clear</a>
                </div>
            </div>
        </form>

        @if($artworks->count() === 0)
            <div class="border rounded p-4 text-center">
                <p class="text-muted mb-3">{{ count(request()->except('tab', 'cursor')) > 0 ? 'No matching images found.' : 'No images uploaded yet.' }}</p>
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
    @php
        $historyEndpoint = $accountBaseRoute . '?' . http_build_query($historyFilterQuery);
        $historyTitle = $historyType === 'bought' ? 'Purchases' : 'Images Sold';
        $historyEmpty = $historyType === 'bought' ? 'No purchases yet.' : 'No sold images yet.';
        $historyPartial = $historyType === 'bought'
            ? 'account.partials.history-bought-row'
            : 'account.partials.history-sold-row';
        $historySectionQuery = request()->only('q', 'sort');
    @endphp

    <div class="tb-card p-4 mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 style="font-size:1.25rem;font-weight:700;margin:0;">{{ $historyTitle }}</h2>
            <nav class="cp-history-switcher" aria-label="Transaction history sections">
                <a
                    href="{{ route('account', array_merge(['tab' => 'history', 'history' => 'sold'], $historySectionQuery)) }}"
                    class="cp-history-link {{ $historyType === 'sold' ? 'is-active' : '' }}"
                    @if($historyType === 'sold') aria-current="page" @endif
                >
                    Images Sold
                </a>
                <a
                    href="{{ route('account', array_merge(['tab' => 'history', 'history' => 'bought'], $historySectionQuery)) }}"
                    class="cp-history-link {{ $historyType === 'bought' ? 'is-active' : '' }}"
                    @if($historyType === 'bought') aria-current="page" @endif
                >
                    Purchases
                </a>
            </nav>
        </div>

        <form method="GET" action="{{ $accountBaseRoute }}" class="cp-filter-panel cp-history-filter-panel mb-3" data-autofilter-form>
            <input type="hidden" name="tab" value="history">
            <input type="hidden" name="history" value="{{ $historyType }}">
            <div>
                <label class="form-label" for="historySearch">Search</label>
                <input class="form-control" id="historySearch" name="q" value="{{ request('q') }}" placeholder="{{ $historyType === 'bought' ? 'Artwork, creator, or purchase number' : 'Artwork, buyer, or purchase number' }}" data-autofilter-debounce>
            </div>
            <div>
                <label class="form-label" for="historySort">Sort</label>
                <select class="form-select" id="historySort" name="sort" data-autofilter-instant>
                    <option value="">Newest</option>
                    <option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option>
                    <option value="price_asc" @selected(request('sort') === 'price_asc')>Price low</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>Price high</option>
                </select>
            </div>
            <div class="cp-filter-actions">
                <a class="btn btn-outline-secondary btn-sm" href="{{ $accountBaseRoute }}?tab=history&history={{ $historyType }}">Clear</a>
            </div>
        </form>

        @if($historyItems->count() === 0)
            <p class="text-muted mb-0">{{ $historyEmpty }}</p>
        @else
            <div
                data-cursor-feed
                data-cursor-endpoint="{{ $historyEndpoint }}"
                data-next-cursor="{{ $historyItems->nextCursor()?->encode() }}"
                data-has-more="{{ $historyItems->hasMorePages() ? '1' : '0' }}"
            >
                <div data-cursor-list>
                    @foreach($historyItems as $item)
                        @include($historyPartial, ['item' => $item, 'user' => $user])
                    @endforeach
                </div>
                @include('partials.cursor-feed-footer')
            </div>
        @endif
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

        document.querySelectorAll('[data-autofilter-form]').forEach(function (form) {
            let timer = null;

            function submitForm() {
                form.requestSubmit();
            }

            form.querySelectorAll('[data-autofilter-debounce]').forEach(function (input) {
                input.addEventListener('input', function () {
                    if (input.matches('[data-cp-tag-text-input]') && input.value.trim().startsWith('#')) {
                        window.clearTimeout(timer);
                        return;
                    }

                    window.clearTimeout(timer);
                    timer = window.setTimeout(submitForm, 450);
                });
            });

            form.querySelectorAll('[data-autofilter-instant]').forEach(function (input) {
                input.addEventListener('change', function () {
                    submitForm();
                });
            });

            function selectedTagValues() {
                return Array.from(form.querySelectorAll('input[name="tags[]"]')).map(function (input) {
                    return input.value;
                });
            }

            function syncTagButtons() {
                const selected = selectedTagValues();

                form.querySelectorAll('[data-cp-tag-button]').forEach(function (button) {
                    button.classList.toggle('is-selected', selected.includes(button.dataset.tag));
                });

                form.querySelectorAll('[data-cp-tag-text-input]').forEach(function (input) {
                    input.classList.toggle('has-tags', selected.length > 0);
                });
            }

            function normalizeTag(value) {
                return value.trim().replace(/^#/, '').replace(/\s+/g, '').toLowerCase();
            }

            function addTag(value) {
                const tag = normalizeTag(value);

                if (!tag || selectedTagValues().includes(tag)) {
                    return false;
                }

                const inputs = form.querySelector('[data-cp-tag-inputs]');
                const chips = form.querySelector('[data-cp-selected-tags]');
                const textInput = form.querySelector('[data-cp-tag-text-input]');

                if (!inputs || !chips || !textInput) {
                    return false;
                }

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tags[]';
                input.value = tag;
                inputs.appendChild(input);

                const chip = document.createElement('span');
                chip.className = 'cp-tag-chip';
                chip.dataset.cpTagChip = tag;
                chip.appendChild(document.createTextNode('#' + tag + ' '));

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.dataset.cpRemoveTag = tag;
                remove.textContent = 'x';
                chip.appendChild(remove);
                chips.insertBefore(chip, textInput);

                syncTagButtons();
                submitForm();
                return true;
            }

            form.querySelectorAll('[data-cp-tag-text-input]').forEach(function (input) {
                input.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter' || !input.value.trim().startsWith('#')) {
                        return;
                    }

                    event.preventDefault();

                    if (addTag(input.value)) {
                        input.value = '';
                    }
                });
            });

            form.querySelectorAll('[data-cp-tag-button]').forEach(function (button) {
                button.addEventListener('click', function () {
                    addTag(button.dataset.tag);
                });
            });

            form.addEventListener('click', function (event) {
                const remove = event.target.closest('[data-cp-remove-tag]');

                if (!remove) {
                    return;
                }

                const tag = remove.dataset.cpRemoveTag;
                form.querySelectorAll('input[name="tags[]"]').forEach(function (input) {
                    if (input.value === tag) {
                        input.remove();
                    }
                });

                form.querySelectorAll('[data-cp-tag-chip="' + CSS.escape(tag) + '"]').forEach(function (chip) {
                    chip.remove();
                });

                syncTagButtons();
                submitForm();
            });

            syncTagButtons();
        });
    });
</script>

@if(in_array($activeTab, ['images', 'history'], true))
    @include('partials.cursor-feed-script')
@endif

@endsection
