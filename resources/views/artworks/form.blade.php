@extends('layouts.master')

@section('title', ($mode === 'create' ? 'Upload' : 'Edit') . ' artwork - ConnectPrint')

@php
    $isEdit = $mode === 'edit';
    $initialTags = collect(explode(',', old('tags', $artwork->tags?->pluck('name')->implode(', ') ?? '')))
        ->map(fn ($tag) => trim($tag))
        ->filter()
        ->values()
        ->all();
    $selectedCategoryId = old('category_id', $isEdit ? $artwork->category_id : '');
    $selectedVisibility = old('visibility', $isEdit ? $artwork->visibility : '');
    $printableChecked = session()->hasOldInput()
        ? old('is_printable') === '1'
        : (bool) $artwork->is_printable;
@endphp

@section('content')
<style>
    .cp-tag-box {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        min-height: 4.8rem;
        max-height: 8rem;
        overflow-y: auto;
        padding: 0.65rem;
        background: #ffffff;
    }

    .cp-tag-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .cp-suggested-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        width: min(36rem, 100%);
        max-height: 7.5rem;
        overflow-x: hidden;
        overflow-y: auto;
        padding: 0.4rem;
        border: 1px solid #d1d5db;
        border-radius: 8px;
    }

    .cp-tag-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 0.35rem 0.65rem;
        background: #f8fafc;
        font-weight: 600;
    }

    .cp-tag-chip button {
        border: none;
        background: transparent;
        color: #64748b;
        font-weight: 700;
        padding: 0;
        line-height: 1;
    }

    .cp-tag-input {
        border: none;
        outline: none;
        min-width: 10rem;
        flex: 1 1 12rem;
        padding: 0.35rem 0.2rem;
    }

    .cp-suggested-tag {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 0.35rem 0.65rem;
        background: #ffffff;
        color: #64748b;
        font-weight: 600;
        white-space: nowrap;
    }

    .cp-image-preview {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        min-height: 12rem;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #f8fafc;
    }

    .cp-image-preview img {
        width: 100%;
        height: 100%;
        max-height: 22rem;
        object-fit: contain;
    }

    .cp-field-error {
        color: #dc2626;
        font-size: 0.85rem;
        margin-top: 0.25rem;
    }

    .cp-file-control {
        display: flex;
        align-items: stretch;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        overflow: hidden;
        background: #ffffff;
    }

    .cp-file-control-button {
        border: none;
        border-right: 1px solid #d1d5db;
        background: #f8fafc;
        padding: 0.55rem 0.9rem;
        font-weight: 600;
    }

    .cp-file-control-name {
        flex: 1;
        min-width: 0;
        padding: 0.55rem 0.9rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>

<div class="tb-card p-4">
    <h1 style="font-size:1.4rem;font-weight:700;">{{ $isEdit ? 'Edit Artwork' : 'Upload Artwork' }}</h1>
    <form
        method="POST"
        action="{{ $isEdit ? route('artworks.update', ['artwork' => $artwork->id]) : route('artworks.store') }}"
        enctype="multipart/form-data"
        novalidate
        id="artworkForm"
        data-draft-url="{{ $isEdit ? route('artworks.draft.update', ['artwork' => $artwork->id]) : route('artworks.draft.store') }}"
    >
        @csrf
        @if($isEdit) @method('PUT') @endif
        <input type="hidden" name="draft_artwork_id" id="draftArtworkId" value="{{ $isEdit ? $artwork->id : '' }}">
        <div class="mb-2">
            <label class="form-label" for="artworkTitle">Title</label>
            <input class="form-control" id="artworkTitle" name="title" value="{{ old('title', $artwork->name) }}" maxlength="150">
            @error('title')
                <div class="cp-field-error">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-2">
            <label class="form-label" for="artworkDescription">Description</label>
            <textarea class="form-control" id="artworkDescription" name="description" rows="4" maxlength="2000">{{ old('description', $artwork->description) }}</textarea>
            @error('description')
                <div class="cp-field-error">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="artworkImage">Upload Image</label>
            <div class="cp-image-preview mb-2" id="imagePreview">
                <span class="text-muted {{ $isEdit && $artwork->preview_path ? 'd-none' : '' }}" id="imagePreviewPlaceholder">No image selected</span>
                <img
                    id="imagePreviewImg"
                    class="{{ $isEdit && $artwork->preview_path ? '' : 'd-none' }}"
                    src="{{ $isEdit && $artwork->preview_path ? $artwork->image_url : '' }}"
                    alt="Selected artwork preview"
                >
            </div>
            <input class="d-none" id="artworkImage" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
            <div class="cp-file-control">
                <button class="cp-file-control-button" id="chooseImageButton" type="button">Choose File</button>
                <div class="cp-file-control-name" id="imageFileName">{{ $isEdit && $artwork->original_filename ? $artwork->original_filename : 'No file chosen' }}</div>
            </div>
            @error('image')
                <div class="cp-field-error">{{ $message }}</div>
            @enderror
        </div>
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label" for="artworkCategory">Category</label>
                <select class="form-select" id="artworkCategory" name="category_id">
                    <option value="" disabled hidden @selected((string) $selectedCategoryId === '')>-</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $selectedCategoryId === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')
                    <div class="cp-field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="artworkVisibility">Visibility</label>
                <select class="form-select" id="artworkVisibility" name="visibility">
                    <option value="" disabled hidden @selected($selectedVisibility === '')>-</option>
                    @foreach(['private', 'unlisted', 'public'] as $visibility)
                        <option value="{{ $visibility }}" @selected($selectedVisibility === $visibility)>{{ ucfirst($visibility) }}</option>
                    @endforeach
                </select>
                @error('visibility')
                    <div class="cp-field-error">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <label class="form-check my-3">
            <input class="form-check-input" id="isPrintableCheckbox" type="checkbox" name="is_printable" value="1" @checked($printableChecked)>
            <span class="form-check-label">Allow other users to purchase printable access</span>
        </label>
        <div class="mb-3 {{ $printableChecked ? '' : 'd-none' }}" id="creatorPriceGroup">
            <label class="form-label" for="creatorPrice">Creator price (Rp)</label>
            <input class="form-control" id="creatorPrice" type="number" name="creator_price" min="1" value="{{ old('creator_price', $artwork->price ?: '') }}" @disabled(! $printableChecked)>
            @error('creator_price')
                <div class="cp-field-error">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Tags</label>
            <input type="hidden" name="tags" id="tagsInput" value="{{ implode(', ', $initialTags) }}">
            <div class="cp-tag-box">
                <div class="cp-tag-list" id="selectedTags" data-initial-tags='@json($initialTags)'>
                    <input class="cp-tag-input" id="tagTextInput" type="text" placeholder="Type a tag and press Enter">
                </div>
            </div>
            <div class="text-muted mt-3 mb-2">Suggested tags</div>
            <div class="cp-suggested-tags" id="suggestedTags">
                @foreach($suggestedTags as $tag)
                    <button class="cp-suggested-tag" type="button" data-tag="{{ $tag }}">{{ $tag }} +</button>
                @endforeach
            </div>
        </div>
        <button class="tb-btn-primary" type="submit">{{ $isEdit ? 'Save Artwork' : 'Upload Artwork' }}</button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectedTags = document.getElementById('selectedTags');
        const tagTextInput = document.getElementById('tagTextInput');
        const hiddenInput = document.getElementById('tagsInput');
        const suggestedTags = document.getElementById('suggestedTags');
        const isPrintableCheckbox = document.getElementById('isPrintableCheckbox');
        const creatorPriceGroup = document.getElementById('creatorPriceGroup');
        const creatorPrice = document.getElementById('creatorPrice');
        const imageInput = document.getElementById('artworkImage');
        const chooseImageButton = document.getElementById('chooseImageButton');
        const imageFileName = document.getElementById('imageFileName');
        const imagePreviewImg = document.getElementById('imagePreviewImg');
        const imagePreviewPlaceholder = document.getElementById('imagePreviewPlaceholder');
        const shouldConfirmImageReplacement = @js($isEdit && (bool) $artwork->original_path);
        const artworkForm = document.getElementById('artworkForm');
        const draftArtworkId = document.getElementById('draftArtworkId');
        const unsavedChangesMessage = 'You have unsaved changes. Are you sure you want to leave this page?';
        let hasUnsavedChanges = false;
        let isSubmittingArtworkForm = false;
        let isSavingDraft = false;
        let historyGuardPushed = false;

        function markArtworkFormDirty() {
            if (!hasUnsavedChanges) {
                hasUnsavedChanges = true;
            }

            if (!historyGuardPushed && window.history && window.history.pushState) {
                window.history.pushState({ artworkFormGuard: true }, '', window.location.href);
                historyGuardPushed = true;
            }
        }

        function clearArtworkFormDirty() {
            isSubmittingArtworkForm = true;
            hasUnsavedChanges = false;
        }

        function hasDraftableContent() {
            if (!artworkForm) {
                return false;
            }

            const title = artworkForm.querySelector('[name="title"]')?.value.trim();
            const description = artworkForm.querySelector('[name="description"]')?.value.trim();
            const category = artworkForm.querySelector('[name="category_id"]')?.value;
            const visibility = artworkForm.querySelector('[name="visibility"]')?.value;
            const tags = artworkForm.querySelector('[name="tags"]')?.value.trim();
            const price = artworkForm.querySelector('[name="creator_price"]')?.value.trim();
            const image = imageInput?.files && imageInput.files.length > 0;

            return Boolean(title || description || category || visibility || tags || price || image);
        }

        function saveDraft(options = {}) {
            if (!artworkForm || !hasDraftableContent() || isSavingDraft) {
                return Promise.resolve();
            }

            const formData = new FormData(artworkForm);
            formData.delete('_method');
            const draftUrl = artworkForm.dataset.draftUrl;

            if (options.beacon && navigator.sendBeacon) {
                navigator.sendBeacon(draftUrl, formData);
                return Promise.resolve();
            }

            isSavingDraft = true;

            return fetch(draftUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Draft save failed.');
                    }

                    return response.json();
                })
                .then(function (payload) {
                    if (payload.id && draftArtworkId) {
                        draftArtworkId.value = payload.id;
                    }

                    if (payload.draft_url) {
                        artworkForm.dataset.draftUrl = payload.draft_url;
                    }
                })
                .catch(function () {
                    return undefined;
                })
                .finally(function () {
                    isSavingDraft = false;
                });
        }

        if (artworkForm) {
            artworkForm.addEventListener('input', markArtworkFormDirty);
            artworkForm.addEventListener('change', markArtworkFormDirty);
            artworkForm.addEventListener('submit', clearArtworkFormDirty);

            window.addEventListener('beforeunload', function (event) {
                if (!hasUnsavedChanges || isSubmittingArtworkForm) {
                    return;
                }

                saveDraft({ beacon: true });
                event.preventDefault();
                event.returnValue = '';
            });

            window.addEventListener('popstate', function () {
                if (!hasUnsavedChanges || isSubmittingArtworkForm) {
                    return;
                }

                if (confirm(unsavedChangesMessage)) {
                    clearArtworkFormDirty();
                    saveDraft().finally(function () {
                        window.history.back();
                    });
                    return;
                }

                window.history.pushState({ artworkFormGuard: true }, '', window.location.href);
            });

            document.addEventListener('click', function (event) {
                if (!hasUnsavedChanges || isSubmittingArtworkForm) {
                    return;
                }

                const link = event.target.closest('a[href]');

                if (!link || link.target === '_blank' || link.hasAttribute('download') || link.getAttribute('href').startsWith('#')) {
                    return;
                }

                event.preventDefault();

                if (!confirm(unsavedChangesMessage)) {
                    return;
                }

                clearArtworkFormDirty();
                saveDraft().finally(function () {
                    window.location.href = link.href;
                });
            });
        }

        if (isPrintableCheckbox && creatorPriceGroup && creatorPrice) {
            function syncPrintablePrice() {
                const enabled = isPrintableCheckbox.checked;
                creatorPriceGroup.classList.toggle('d-none', !enabled);
                creatorPrice.disabled = !enabled;
            }

            isPrintableCheckbox.addEventListener('change', syncPrintablePrice);
            syncPrintablePrice();
        }

        if (imageInput && imagePreviewImg && imagePreviewPlaceholder) {
            imageInput.addEventListener('change', function () {
                const file = imageInput.files && imageInput.files[0];

                if (!file) {
                    if (imageFileName) {
                        imageFileName.textContent = @js($isEdit && $artwork->original_filename ? $artwork->original_filename : 'No file chosen');
                    }
                    imagePreviewImg.classList.add('d-none');
                    imagePreviewImg.removeAttribute('src');
                    imagePreviewPlaceholder.classList.remove('d-none');
                    return;
                }

                if (imageFileName) {
                    imageFileName.textContent = file.name;
                }
                imagePreviewImg.src = URL.createObjectURL(file);
                imagePreviewImg.classList.remove('d-none');
                imagePreviewPlaceholder.classList.add('d-none');
                markArtworkFormDirty();
            });
        }

        if (chooseImageButton && imageInput) {
            chooseImageButton.addEventListener('click', function () {
                if (
                    shouldConfirmImageReplacement
                    && !confirm('This will change the uploaded artwork image. Continue?')
                ) {
                    return;
                }

                imageInput.click();
            });
        }

        if (!selectedTags || !tagTextInput || !hiddenInput) {
            return;
        }

        const tags = new Set(JSON.parse(selectedTags.dataset.initialTags || '[]'));

        function normalizeTag(value) {
            return value.trim().replace(/^#/, '').replace(/\s+/g, '').toLowerCase();
        }

        function syncTags() {
            hiddenInput.value = Array.from(tags).join(', ');
        }

        function renderTags() {
            selectedTags.querySelectorAll('.cp-tag-chip').forEach(function (chip) {
                chip.remove();
            });

            Array.from(tags).forEach(function (tag) {
                const chip = document.createElement('span');
                chip.className = 'cp-tag-chip';
                chip.textContent = tag + ' ';

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.textContent = 'x';
                remove.addEventListener('click', function () {
                    tags.delete(tag);
                    renderTags();
                    markArtworkFormDirty();
                });

                chip.appendChild(remove);
                selectedTags.insertBefore(chip, tagTextInput);
            });

            syncTags();
        }

        function addTag(value) {
            const tag = normalizeTag(value);
            if (!tag) {
                return;
            }

            tags.add(tag);
            tagTextInput.value = '';
            renderTags();
            markArtworkFormDirty();
        }

        tagTextInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ',') {
                event.preventDefault();
                addTag(tagTextInput.value);
            }
        });

        tagTextInput.addEventListener('blur', function () {
            addTag(tagTextInput.value);
        });

        if (suggestedTags) {
            suggestedTags.addEventListener('click', function (event) {
                const button = event.target.closest('[data-tag]');
                if (!button) {
                    return;
                }

                addTag(button.dataset.tag);
            });
        }

        renderTags();
    });
</script>
@endsection
