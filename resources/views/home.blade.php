@extends('layouts.master')

@section('title', 'ConnectPrint - Artwork Marketplace')

@php
    use Illuminate\Support\Str;
    $userId = session('user_id');
    $userSlug = $userId ? Str::slug(session('name')) : null;
    $categoryBaseRoute = $userId ? route('home.user') : route('home');
    $clearCategoryQuery = collect(request()->except('category'))
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->all();
    $clearCategoryUrl = $categoryBaseRoute . ($clearCategoryQuery ? '?' . http_build_query($clearCategoryQuery) : '');
@endphp

@section('content')
<style>
    .cp-category-menu {
        max-height: 13rem;
        overflow-y: auto;
    }

    .cp-category-option {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.35rem 0;
        white-space: nowrap;
    }

    .cp-filter-grid {
        display: grid;
        grid-template-columns: minmax(260px, 1.45fr) minmax(220px, 1fr) minmax(150px, 0.65fr) minmax(150px, 0.65fr);
        gap: 0.75rem;
        align-items: end;
    }

    .cp-filter-actions {
        display: flex;
        justify-content: flex-end;
    }

    .cp-filter-footer {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: minmax(260px, 1.45fr) minmax(220px, 1fr) minmax(150px, 0.65fr) minmax(150px, 0.65fr);
        gap: 0.75rem;
        align-items: start;
    }

    .cp-filter-label {
        margin-bottom: 0;
    }

    .cp-search-tag-box {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        max-height: 4.8rem;
        overflow-x: auto;
        overflow-y: hidden;
        padding: 0.55rem;
        background: #ffffff;
    }

    .cp-search-tag-list {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.45rem;
        min-width: 0;
        width: max-content;
        max-width: none;
    }

    .cp-suggested-tag-menu {
        max-height: 12rem;
        overflow-y: auto;
        width: min(24rem, 85vw);
    }

    .cp-suggested-tag-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }

    .cp-search-tag-input {
        border: none;
        outline: none;
        min-width: 18rem;
        flex: 0 0 18rem;
        padding: 0.25rem 0.15rem;
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
        padding: 0.25rem 0.6rem;
        font-size: 0.8rem;
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

    .cp-suggested-tag {
        display: inline-flex;
        width: auto;
        text-align: center;
        background: #ffffff;
    }

    .cp-suggested-tag.is-selected {
        border-color: var(--tb-blue);
        color: var(--tb-blue);
        background: #ecfdf5;
    }

    @media (max-width: 991.98px) {
        .cp-filter-grid {
            grid-template-columns: 1fr 1fr;
        }

        .cp-filter-footer {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 575.98px) {
        .cp-filter-grid {
            grid-template-columns: 1fr;
        }

        .cp-filter-footer {
            grid-template-columns: 1fr;
        }

        .cp-filter-actions {
            justify-content: flex-start;
        }
    }
</style>

<section class="mb-4">
    <div class="tb-card p-4" style="background:#0f172a;color:#fff;border:none;">
        <span class="badge rounded-pill" style="background:#facc15;color:#111827;">CONNECTPRINT MARKETPLACE</span>
        <h1 class="mt-2 mb-2" style="font-size:1.7rem;font-weight:700;">Browse printable artwork</h1>
        <p class="mb-0" style="color:#d1d5db;">Purchase creator-priced print-ready files here, then submit them manually to Printbox. Printbox fees and QR codes are handled by Printbox.</p>
    </div>
</section>

<section class="tb-card p-3 mb-3">
    <form method="GET" action="{{ $categoryBaseRoute }}" class="cp-filter-grid" id="marketplaceFilterForm">
        <div>
            <label class="form-label cp-filter-label" for="marketplaceSearchInput">Search</label>
        </div>
        <div>
            <label class="form-label cp-filter-label d-block" for="homeCategoryDropdown">Category</label>
        </div>
        <div>
            <label class="form-label cp-filter-label" for="marketplacePrintableSelect">Type</label>
        </div>
        <div>
            <label class="form-label cp-filter-label" for="marketplaceSortSelect">Sort</label>
        </div>
        <div>
            <div data-cp-tag-inputs>
                @foreach(($selectedTags ?? []) as $tag)
                    <input type="hidden" name="tags[]" value="{{ $tag }}">
                @endforeach
            </div>
            <div class="cp-search-tag-box">
                <div class="cp-search-tag-list" data-cp-selected-tags aria-label="Search tags">
                    @foreach(($selectedTags ?? []) as $tag)
                        <span class="cp-tag-chip" data-cp-tag-chip="{{ $tag }}">#{{ $tag }} <button type="button" data-cp-remove-tag="{{ $tag }}">x</button></span>
                    @endforeach
                    <input class="cp-search-tag-input" id="marketplaceSearchInput" name="q" value="{{ request('q') }}" placeholder="Search or type #tag and press Enter" data-filter-autosubmit="debounced" data-cp-tag-text-input>
                </div>
            </div>
        </div>
        <div>
            <div class="dropdown">
                <button
                    class="form-select text-start"
                    type="button"
                    id="homeCategoryDropdown"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                >
                    @if(!empty($categoryIds))
                        {{ count($categoryIds) }} selected
                    @else
                        All categories
                    @endif
                </button>
                <div class="dropdown-menu p-3 w-100" aria-labelledby="homeCategoryDropdown">
                    <div class="cp-category-menu">
                        @foreach($categories as $category)
                            <label class="cp-category-option">
                                <input
                                    class="form-check-input m-0"
                                    type="checkbox"
                                    name="category[]"
                                    value="{{ $category->id }}"
                                    @checked(in_array($category->id, $categoryIds ?? [], true))
                                >
                                <span>{{ $category->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="d-flex gap-2 pt-2">
                        <button class="btn btn-primary btn-sm" type="submit">Apply all</button>
                        <a class="btn btn-outline-secondary btn-sm" href="{{ $clearCategoryUrl }}">Clear</a>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <select class="form-select" id="marketplacePrintableSelect" name="printable" data-filter-autosubmit="instant">
                <option value="">All</option>
                <option value="printable" @selected(request('printable') === 'printable')>Printable</option>
                <option value="display" @selected(request('printable') === 'display')>Display only</option>
            </select>
        </div>
        <div>
            <select class="form-select" id="marketplaceSortSelect" name="sort" data-filter-autosubmit="instant">
                <option value="">Newest</option>
                <option value="price_asc" @selected(request('sort') === 'price_asc')>Price low</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>Price high</option>
            </select>
        </div>
        <div class="cp-filter-footer">
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
            <div class="cp-filter-actions">
                <a class="btn btn-outline-secondary btn-sm" href="{{ $categoryBaseRoute }}">Clear all filters</a>
            </div>
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
                        <a href="{{ route('artworks.show', $artwork->id) }}" class="ratio ratio-4x3 d-block">
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('marketplaceFilterForm');

        if (!form) {
            return;
        }

        let debounceTimer = null;

        function submitFilter() {
            form.requestSubmit();
        }

        form.querySelectorAll('[data-filter-autosubmit="debounced"]').forEach(function (input) {
            input.addEventListener('input', function () {
                if (input.value.trim().startsWith('#')) {
                    window.clearTimeout(debounceTimer);
                    return;
                }

                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(submitFilter, 400);
            });
        });

        form.querySelectorAll('[data-filter-autosubmit="instant"]').forEach(function (input) {
            input.addEventListener('change', submitFilter);
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

        function removeTag(tag) {
            form.querySelectorAll('input[name="tags[]"]').forEach(function (input) {
                if (input.value === tag) {
                    input.remove();
                }
            });

            form.querySelectorAll('[data-cp-tag-chip="' + CSS.escape(tag) + '"]').forEach(function (chip) {
                chip.remove();
            });

            syncTagButtons();
        }

        form.querySelectorAll('[data-cp-tag-button]').forEach(function (button) {
            button.addEventListener('click', function () {
                addTag(button.dataset.tag);
            });
        });

        form.addEventListener('click', function (event) {
            const remove = event.target.closest('[data-cp-remove-tag]');
            if (remove) {
                removeTag(remove.dataset.cpRemoveTag);
            }

        });

        syncTagButtons();
    });
</script>
@endsection
