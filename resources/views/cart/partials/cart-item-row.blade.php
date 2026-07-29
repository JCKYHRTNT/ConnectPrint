@php
    $artwork = $item->artwork;
    $isPrintOnly = $item->printbox_requested && $artwork->canDownloadFileBy($viewer ?? null);
    $itemCreatorPrice = $isPrintOnly
        ? 0
        : (int) $artwork->price;
    $sheetCount = (int) ($item->printbox_sheet_count ?: 1);
    $printboxMode = $item->printbox_mode ?: 'bw';
    $itemPrintboxFee = $item->printbox_requested
        ? ($printboxMode === 'bw' && $sheetCount >= 10
            ? $sheetCount * $printboxRates['bw_bulk']
            : $sheetCount * ($printboxMode === 'color' ? $printboxRates['color'] : $printboxRates['bw_low']))
        : 0;
@endphp

<div class="d-flex align-items-center gap-3 border rounded p-2" data-cursor-item="cart-item-{{ $item->id }}">
    <img src="{{ $artwork->image_url }}" alt="{{ $artwork->name }}" style="width:86px;height:86px;object-fit:cover;border-radius:8px;">
    <div class="flex-grow-1">
        <div style="font-weight:700;">{{ $artwork->name }}</div>
        <div class="text-muted small">Creator: {{ $artwork->creatorName() }}</div>
        @if($itemCreatorPrice > 0 && ! $isPrintOnly)
            <div style="font-weight:700;color:var(--tb-blue);">Creator Price: Rp{{ number_format($itemCreatorPrice, 0, ',', '.') }}</div>
        @endif
        @if($isPrintOnly)
            <div class="small text-muted">
                Printbox sheets total: Rp{{ number_format($itemPrintboxFee, 0, ',', '.') }}
                ({{ $sheetCount }} sheet{{ $sheetCount === 1 ? '' : 's' }})
            </div>
            <form method="POST" action="{{ route('cart.item.printbox.update', ['item' => $item->id]) }}" class="cp-cart-printbox-panel" data-cart-printbox-submit-form>
                @csrf
                <input type="hidden" name="printbox_requested" value="1">
                <div class="cp-cart-printbox-grid">
                    <div>
                        <label class="form-label small" for="printboxMode{{ $item->id }}">Print type</label>
                        <select class="form-select form-select-sm" id="printboxMode{{ $item->id }}" name="printbox_mode" data-cart-printbox-autosubmit>
                            <option value="bw" @selected($printboxMode === 'bw')>Black and White</option>
                            <option value="color" @selected($printboxMode === 'color')>Color / Partial Color</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small" for="printboxSheets{{ $item->id }}">Sheets</label>
                        <input class="form-control form-control-sm" id="printboxSheets{{ $item->id }}" type="number" name="printbox_sheet_count" min="1" max="200" value="{{ $sheetCount }}" data-cart-printbox-autosubmit>
                    </div>
                </div>
            </form>
        @else
            @if($item->printbox_requested && $itemPrintboxFee > 0)
                <div class="small text-muted">
                    Printbox sheets total: Rp{{ number_format($itemPrintboxFee, 0, ',', '.') }}
                    ({{ $sheetCount }} sheet{{ $sheetCount === 1 ? '' : 's' }})
                </div>
            @endif
            <form method="POST" action="{{ route('cart.item.printbox.update', ['item' => $item->id]) }}" class="cp-cart-printbox-panel" data-cart-printbox-form data-cart-printbox-submit-form>
                @csrf
                <label class="form-check-label d-inline-flex align-items-center gap-2 text-muted small">
                    <input class="form-check-input m-0" type="checkbox" name="printbox_requested" value="1" @checked($item->printbox_requested) data-cart-printbox-toggle>
                    <span>Print with Printbox</span>
                </label>
                <div class="{{ $item->printbox_requested ? '' : 'd-none' }} mt-2" data-cart-printbox-options>
                    <div class="cp-cart-printbox-grid">
                        <div>
                            <label class="form-label small" for="printboxMode{{ $item->id }}">Print type</label>
                            <select class="form-select form-select-sm" id="printboxMode{{ $item->id }}" name="printbox_mode" data-cart-printbox-autosubmit>
                                <option value="bw" @selected($printboxMode === 'bw')>Black and White</option>
                                <option value="color" @selected($printboxMode === 'color')>Color / Partial Color</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label small" for="printboxSheets{{ $item->id }}">Sheets</label>
                            <input class="form-control form-control-sm" id="printboxSheets{{ $item->id }}" type="number" name="printbox_sheet_count" min="1" max="200" value="{{ $sheetCount }}" data-cart-printbox-autosubmit>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>
    <form method="POST" action="{{ route('cart.item.update', ['item' => $item->id]) }}">
        @csrf
        <button class="btn btn-outline-danger btn-sm" type="submit">Remove</button>
    </form>
</div>
