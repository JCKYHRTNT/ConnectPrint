@extends('layouts.master')

@section('title', 'Personal library - ConnectPrint')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 style="font-size:1.5rem;font-weight:700;">Personal library</h1>
    <a class="tb-btn-primary" href="{{ route('artworks.create') }}">Upload artwork</a>
</div>
@if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if(session('error')) <div class="alert alert-warning">{{ session('error') }}</div> @endif

<div class="tb-card p-3 mb-3">
    @foreach(['all', 'draft', 'public', 'unlisted', 'private', 'archived', 'printable', 'display-only'] as $filter)
        <a class="btn btn-sm {{ request('filter', 'all') === $filter ? 'btn-primary' : 'btn-outline-secondary' }} me-1" href="{{ route('artworks.index', ['filter' => $filter]) }}">{{ ucfirst($filter) }}</a>
    @endforeach
</div>

@if($artworks->isEmpty())
    <div class="tb-card p-4">No artwork in this filter.</div>
@else
    <div class="row g-3">
        @foreach($artworks as $artwork)
            <div class="col-md-4">
                <div class="tb-card p-3 h-100">
                    <img src="{{ $artwork->image_url }}" alt="{{ $artwork->name }}" class="w-100 mb-2" style="height:180px;object-fit:cover;border-radius:8px;">
                    <h2 style="font-size:1.05rem;font-weight:700;">{{ $artwork->name }}</h2>
                    <div class="d-flex gap-1 flex-wrap mb-2">
                        <span class="badge text-bg-dark">{{ ucfirst($artwork->visibility) }}</span>
                        <span class="badge text-bg-info">{{ ucfirst($artwork->moderation_status) }}</span>
                        <span class="badge {{ $artwork->is_printable ? 'text-bg-primary' : 'text-bg-secondary' }}">{{ $artwork->is_printable ? 'Printable' : 'Display only' }}</span>
                    </div>
                    @if($artwork->share_token)
                        <input class="form-control form-control-sm mb-2" value="{{ route('artworks.shared', $artwork->share_token) }}" readonly>
                    @endif
                    <div class="d-flex gap-1 flex-wrap">
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('artworks.show', $artwork->id) }}">View</a>
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('artworks.edit', ['artwork' => $artwork->id]) }}">Edit</a>
                        @if($artwork->isArchived())
                            <form method="POST" action="{{ route('artworks.restore', ['artwork' => $artwork->id]) }}">@csrf @method('PATCH')<button class="btn btn-outline-success btn-sm">Restore</button></form>
                        @else
                            <form method="POST" action="{{ route('artworks.archive', ['artwork' => $artwork->id]) }}">@csrf @method('PATCH')<button class="btn btn-outline-warning btn-sm">Archive</button></form>
                        @endif
                        <form method="POST" action="{{ route('artworks.destroy', ['artwork' => $artwork->id]) }}" onsubmit="return confirm('Delete unused artwork or archive purchased artwork?');">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete</button></form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-3">{{ $artworks->links() }}</div>
@endif
@endsection
