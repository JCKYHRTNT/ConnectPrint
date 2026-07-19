<div class="cp-list-row mb-2" data-cursor-item="sold-{{ $item->id }}">
    <span>
        <strong>{{ $item->artwork_title_snapshot }}</strong>
        <span class="d-block text-muted small">
            Buyer: {{ $item->purchase->user->name ?? 'Unknown' }}
            @if($item->purchase)
                - {{ $item->purchase->purchase_number }}
            @endif
        </span>
    </span>
    <strong>Rp{{ number_format($item->creator_price, 0, ',', '.') }}</strong>
</div>
