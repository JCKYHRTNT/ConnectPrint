<div class="cp-artwork-card" data-cursor-item="{{ $artwork->id }}">
    <a href="{{ route('artworks.show', $artwork->id) }}" class="cp-artwork-frame cp-artwork-frame-card">
        <img src="{{ $artwork->image_url }}" alt="{{ $artwork->name }}" class="cp-artwork-image-contained">
    </a>
    <div class="p-3">
        <div class="d-flex gap-1 flex-wrap mb-2">
            <span class="badge text-bg-warning">{{ $artwork->category->name ?? 'Uncategorized' }}</span>
            <span class="badge {{ $artwork->is_printable ? 'text-bg-primary' : 'text-bg-secondary' }}">
                {{ $artwork->is_printable ? 'Printable' : 'Display only' }}
            </span>
            @if(in_array($artwork->moderation_status, ['draft', 'rejected'], true))
                <span class="badge text-bg-info">{{ ucfirst($artwork->moderation_status) }}</span>
            @endif
            <span class="badge text-bg-dark">{{ ucfirst($artwork->visibility) }}</span>
        </div>
        <h3 class="mb-1" style="font-size:1rem;font-weight:700;">{{ $artwork->name }}</h3>
        <div class="text-muted small">by {{ $artwork->creatorName() }}</div>
        @if($artwork->visibility === 'unlisted' && $artwork->share_token)
            <button
                class="btn btn-outline-secondary btn-sm mt-2"
                type="button"
                data-copy-artwork-link="{{ route('artworks.shared', $artwork->share_token) }}"
            >
                Copy link
            </button>
        @endif
        <div class="mt-2 mb-3" style="font-weight:700;color:var(--tb-blue);">
            @if($artwork->is_printable && (int) $artwork->price > 0)
                Rp{{ number_format($artwork->price, 0, ',', '.') }}
            @else
                &nbsp;
            @endif
        </div>
        <div class="cp-card-actions">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('artworks.edit', ['artwork' => $artwork->id]) }}">Edit</a>
            @if($artwork->isArchived())
                <form method="POST" action="{{ route('artworks.restore', ['artwork' => $artwork->id]) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-outline-success btn-sm" type="submit">Unarchive</button>
                </form>
            @else
                <form method="POST" action="{{ route('artworks.archive', ['artwork' => $artwork->id]) }}" onsubmit="return confirm('Archive this image?');">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-outline-warning btn-sm" type="submit">Archive</button>
                </form>
            @endif
            <form method="POST" action="{{ route('artworks.destroy', ['artwork' => $artwork->id]) }}" onsubmit="return confirm('Delete unused image or archive purchased image?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
            </form>
        </div>
    </div>
</div>
