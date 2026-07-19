<div class="col-6 col-md-4 col-lg-3" data-cursor-item="{{ $artwork->id }}">
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
