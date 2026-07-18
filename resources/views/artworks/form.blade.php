@extends('layouts.master')

@section('title', ($mode === 'create' ? 'Upload' : 'Edit') . ' artwork - ConnectPrint')

@php
    $isEdit = $mode === 'edit';
    $initialTags = collect(explode(',', old('tags', $artwork->tags?->pluck('name')->implode(', ') ?? '')))
        ->map(fn ($tag) => trim($tag))
        ->filter()
        ->values()
        ->all();
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

    .cp-tag-list,
    .cp-suggested-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
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
    }
</style>

<div class="tb-card p-4">
    <h1 style="font-size:1.4rem;font-weight:700;">{{ $isEdit ? 'Edit artwork' : 'Upload artwork' }}</h1>
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ $isEdit ? route('artworks.update', ['artwork' => $artwork->id]) : route('artworks.store') }}" enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif
        <div class="mb-2">
            <label class="form-label">Title</label>
            <input class="form-control" name="title" value="{{ old('title', $artwork->name) }}" maxlength="150" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="4" maxlength="2000">{{ old('description', $artwork->description) }}</textarea>
        </div>
        @if(! $isEdit)
            <div class="mb-2">
                <label class="form-label">Image</label>
                <input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png,.webp" required>
            </div>
        @endif
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label">Category</label>
                <select class="form-select" name="category_id" required>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $artwork->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Visibility</label>
                <select class="form-select" name="visibility" required>
                    @foreach(['public', 'unlisted', 'private'] as $visibility)
                        <option value="{{ $visibility }}" @selected(old('visibility', $artwork->visibility) === $visibility)>{{ ucfirst($visibility) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Creator price (Rp)</label>
                <input class="form-control" type="number" name="creator_price" min="0" value="{{ old('creator_price', $artwork->price ?? 0) }}">
            </div>
        </div>
        <label class="form-check my-3">
            <input class="form-check-input" type="checkbox" name="is_printable" value="1" @checked(old('is_printable', $artwork->is_printable))>
            <span class="form-check-label">Allow other users to purchase printable access</span>
        </label>
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
        <button class="tb-btn-primary" type="submit">{{ $isEdit ? 'Save artwork' : 'Upload artwork' }}</button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectedTags = document.getElementById('selectedTags');
        const tagTextInput = document.getElementById('tagTextInput');
        const hiddenInput = document.getElementById('tagsInput');
        const suggestedTags = document.getElementById('suggestedTags');

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
