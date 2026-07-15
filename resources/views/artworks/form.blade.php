@extends('layouts.master')

@section('title', ($mode === 'create' ? 'Upload' : 'Edit') . ' artwork - ConnectPrint')

@php
    use Illuminate\Support\Str;
    $userSlug = Str::slug(session('name'));
    $isEdit = $mode === 'edit';
@endphp

@section('content')
<div class="tb-card p-4">
    <h1 style="font-size:1.4rem;font-weight:700;">{{ $isEdit ? 'Edit artwork' : 'Upload artwork' }}</h1>
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ $isEdit ? route('artworks.update', ['username' => $userSlug, 'artwork' => $artwork->id]) : route('artworks.store', ['username' => $userSlug]) }}" enctype="multipart/form-data">
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
            <label class="form-label">Tags, comma-separated, maximum five</label>
            <input class="form-control" name="tags" value="{{ old('tags', $artwork->tags?->pluck('name')->implode(', ')) }}">
        </div>
        <button class="tb-btn-primary" type="submit">{{ $isEdit ? 'Save artwork' : 'Upload artwork' }}</button>
    </form>
</div>
@endsection
