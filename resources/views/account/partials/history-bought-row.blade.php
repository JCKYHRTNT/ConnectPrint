<div class="cp-list-row mb-2" data-cursor-item="bought-{{ $item->id }}">
    <span>
        <strong>{{ $item->artwork_title_snapshot }}</strong>
        <span class="d-block text-muted small">Creator: {{ $item->creator_name_snapshot }}</span>
    </span>
    <span class="d-flex gap-2 flex-wrap justify-content-end">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('purchases.show', ['purchase' => $item->purchase_id]) }}">View purchase</a>
        @if($item->artwork && $item->artwork->canDownloadFileBy($user))
            <a class="btn btn-outline-primary btn-sm" href="{{ route('artworks.print-file', ['artwork' => $item->product_id]) }}">Open file</a>
        @endif
    </span>
</div>
