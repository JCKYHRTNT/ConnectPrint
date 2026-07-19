<div class="cp-artwork-card" data-cursor-item="{{ $artwork->id }}">
    <a href="{{ route('artworks.show', $artwork->id) }}">
        <img src="{{ $artwork->image_url }}" alt="{{ $artwork->name }}" class="cp-artwork-thumb">
    </a>
    <div class="p-3">
        <div class="d-flex gap-1 flex-wrap mb-2">
            <span class="badge text-bg-warning">{{ $artwork->category->name ?? 'Uncategorized' }}</span>
            <span class="badge {{ $artwork->is_printable ? 'text-bg-primary' : 'text-bg-secondary' }}">
                {{ $artwork->is_printable ? 'Printable' : 'Display only' }}
            </span>
            <span class="badge text-bg-info">{{ ucfirst($artwork->moderation_status) }}</span>
            <span class="badge text-bg-dark">{{ ucfirst($artwork->visibility) }}</span>
        </div>
        <h3 class="mb-1" style="font-size:1rem;font-weight:700;">{{ $artwork->name }}</h3>
        <div class="text-muted small mb-2">{{ $artwork->original_filename ?: 'No original filename' }}</div>
        <div class="mt-2 mb-3" style="font-weight:700;color:var(--tb-blue);">Rp{{ number_format($artwork->price, 0, ',', '.') }}</div>
        <div class="cp-card-actions">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('artworks.edit', ['artwork' => $artwork->id]) }}">Edit</a>
            <form method="POST" action="{{ route('artworks.destroy', ['artwork' => $artwork->id]) }}" onsubmit="return confirm('Delete unused image or archive purchased image?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
            </form>
        </div>
    </div>
</div>
