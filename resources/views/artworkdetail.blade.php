@extends('layouts.master')

@section('title', $artwork->name . ' - ConnectPrint')

@php
    use Illuminate\Support\Str;
    $loggedIn = session('user_id') !== null;
    $userSlug = $loggedIn ? Str::slug(session('name')) : null;
    $isOwner = $viewer && (int) $viewer->id === (int) $artwork->user_id;
    $canDownloadFile = $artwork->canDownloadFileBy($viewer);
    $printboxRates = $printboxRates ?? ['bw_low' => 750, 'bw_bulk' => 500, 'color' => 750];
@endphp

@section('content')
<style>
    .cp-artwork-actions {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .cp-primary-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .cp-printbox-option {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        margin: 0;
        line-height: 1.2;
    }

    .cp-printbox-option .form-check-input {
        flex: 0 0 auto;
        margin: 0;
    }

    .cp-printbox-panel {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.85rem;
        background: #ffffff;
        max-width: 34rem;
    }

    .cp-printbox-grid {
        display: grid;
        grid-template-columns: minmax(180px, 1fr) minmax(110px, 0.55fr);
        gap: 0.65rem;
    }

    .cp-printbox-rates {
        color: var(--tb-gray-text);
        font-size: 0.8rem;
        margin-top: 0.55rem;
    }

    .cp-printbox-rates span {
        display: block;
    }

    @media (max-width: 575.98px) {
        .cp-printbox-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="tb-card p-4">
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="row g-4">
        <div class="col-md-5">
            <div class="cp-artwork-frame cp-artwork-frame-detail">
                <img src="{{ $artwork->image_url }}" alt="{{ $artwork->name }}" class="cp-artwork-image-contained">
            </div>
        </div>
        <div class="col-md-7">
            <div class="d-flex gap-1 flex-wrap mb-2">
                <span class="badge text-bg-warning">{{ $artwork->category->name ?? 'Uncategorized' }}</span>
                <span class="badge {{ $artwork->is_printable ? 'text-bg-primary' : 'text-bg-secondary' }}">{{ $artwork->is_printable ? 'Printable' : 'Display only' }}</span>
                @if($isOwner || ($viewer && $viewer->role === 'admin'))
                    <span class="badge text-bg-dark">{{ ucfirst($artwork->visibility) }}</span>
                    @if($artwork->isArchived())
                        <span class="badge text-bg-secondary">Archived</span>
                    @endif
                    @if(in_array($artwork->moderation_status, ['draft', 'rejected'], true))
                        <span class="badge text-bg-info">{{ ucfirst($artwork->moderation_status) }}</span>
                    @endif
                @endif
            </div>

            <h1 style="font-size:1.6rem;font-weight:700;">{{ $artwork->name }}</h1>
            <p class="mb-1">Creator: <a href="{{ route('creators.show', $artwork->user_id ?: 1) }}" style="color:var(--tb-blue);">{{ $artwork->creatorName() }}</a></p>
            <p style="font-size:1.1rem;font-weight:700;color:var(--tb-blue);">
                @if($artwork->is_printable && (int) $artwork->price > 0)
                    Rp{{ number_format($artwork->price, 0, ',', '.') }}
                @else
                    &nbsp;
                @endif
            </p>
            <p style="white-space:pre-line;color:var(--tb-gray-text);">{{ $artwork->description ?: 'No description available.' }}</p>

            @if($artwork->tags->isNotEmpty())
                <div class="d-flex gap-1 flex-wrap mb-3">
                    @foreach($artwork->tags as $tag)
                        <span class="badge text-bg-light">#{{ $tag->name }}</span>
                    @endforeach
                </div>
            @endif

            @if($isOwner)
                <form method="POST" action="{{ route('artworks.visibility.update', ['artwork' => $artwork->id]) }}" class="border rounded p-3 mb-3" style="max-width:24rem;">
                    @csrf
                    @method('PATCH')
                    <label class="form-label" for="detailVisibility">Visibility</label>
                    <div class="d-flex gap-2">
                        <select class="form-select" id="detailVisibility" name="visibility">
                            <option value="private" @selected($artwork->visibility === 'private')>Private</option>
                            <option value="unlisted" @selected($artwork->visibility === 'unlisted')>Unlisted</option>
                            <option value="public" @selected($artwork->visibility === 'public')>Public</option>
                        </select>
                        <button class="btn btn-outline-secondary btn-sm" type="submit">Save</button>
                    </div>
                    @if($artwork->visibility === 'unlisted' && $artwork->share_token)
                        <button
                            class="btn btn-outline-secondary btn-sm mt-2"
                            type="button"
                            data-copy-artwork-link="{{ route('artworks.shared', $artwork->share_token) }}"
                        >
                            Copy link
                        </button>
                    @endif
                </form>
            @endif

            <div class="cp-artwork-actions">
                <div class="cp-primary-actions">
                @if($canDownloadFile)
                    <a class="tb-btn-primary" href="{{ route('artworks.print-file', ['artwork' => $artwork->id]) }}">Download file</a>
                    <form method="POST" action="{{ route('cart.add', ['artwork' => $artwork->id]) }}" class="cp-printbox-panel">
                        @csrf
                        <input type="hidden" name="printbox_requested" value="1">
                        <div class="mb-2" style="font-weight:700;color:var(--tb-blue);" data-printbox-price>Printbox sheets total: Rp{{ number_format($printboxRates['bw_low'], 0, ',', '.') }}</div>
                        <div class="cp-printbox-grid">
                            <div>
                                <label class="form-label" for="ownedPrintboxMode">Print type</label>
                                <select class="form-select" id="ownedPrintboxMode" name="printbox_mode" data-printbox-calc-mode>
                                    <option value="bw">Black and White</option>
                                    <option value="color">Color / Partial Color</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="ownedPrintboxSheets">Sheets</label>
                                <input class="form-control" id="ownedPrintboxSheets" type="number" name="printbox_sheet_count" min="1" max="200" value="1" data-printbox-calc-sheets>
                            </div>
                        </div>
                        <div class="cp-printbox-rates">
                            <span>Black and White 0-9: Rp{{ number_format($printboxRates['bw_low'], 0, ',', '.') }}/sheet.</span>
                            <span>Black and White &gt;10: Rp{{ number_format($printboxRates['bw_bulk'], 0, ',', '.') }}/sheet.</span>
                            <span>Color / Partial Color: Rp{{ number_format($printboxRates['color'], 0, ',', '.') }}/sheet.</span>
                        </div>
                        <button class="tb-btn-primary mt-3" type="submit">Add to cart</button>
                    </form>
                @elseif(! $artwork->is_printable)
                    <button class="btn btn-secondary btn-sm" disabled>Display only - printing is not permitted</button>
                @elseif($canPurchase)
                    <form method="POST" action="{{ route('cart.add', ['artwork' => $artwork->id]) }}" data-printbox-toggle-form>
                        @csrf
                        <label class="cp-printbox-option mb-2">
                            <input class="form-check-input" type="checkbox" name="printbox_requested" value="1" data-printbox-toggle>
                            <span>Print with Printbox after buying</span>
                        </label>
                        <div class="cp-printbox-panel mb-3 d-none" data-printbox-options>
                            <div class="cp-printbox-grid">
                                <div>
                                    <label class="form-label" for="purchasePrintboxMode">Print type</label>
                                    <select class="form-select" id="purchasePrintboxMode" name="printbox_mode" data-printbox-calc-mode>
                                        <option value="bw">Black and White</option>
                                        <option value="color">Color / Partial Color</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" for="purchasePrintboxSheets">Sheets</label>
                                    <input class="form-control" id="purchasePrintboxSheets" type="number" name="printbox_sheet_count" min="1" max="200" value="1" data-printbox-calc-sheets>
                                </div>
                            </div>
                            <div class="cp-printbox-rates">
                                <div style="font-weight:700;color:var(--tb-blue);" data-printbox-price>Printbox sheets total: Rp{{ number_format($printboxRates['bw_low'], 0, ',', '.') }}</div>
                                <span>Black and White 0-9: Rp{{ number_format($printboxRates['bw_low'], 0, ',', '.') }}/sheet.</span>
                                <span>Black and White &gt;10: Rp{{ number_format($printboxRates['bw_bulk'], 0, ',', '.') }}/sheet.</span>
                                <span>Color / Partial Color: Rp{{ number_format($printboxRates['color'], 0, ',', '.') }}/sheet.</span>
                            </div>
                        </div>
                        <button class="tb-btn-primary" type="submit">Add to cart</button>
                    </form>
                @elseif($loggedIn)
                    <button class="btn btn-secondary btn-sm" disabled>Not available for purchase</button>
                @else
                    <a class="tb-btn-primary" href="{{ route('login') }}">Login to purchase</a>
                @endif
                </div>

                @if($loggedIn && ! $isOwner)
                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#reportForm">Report</button>
                @endif
            </div>

            <div id="reportForm" class="collapse mt-3">
                <form method="POST" action="{{ route('artworks.reports.store', ['artwork' => $artwork->id]) }}" class="border rounded p-3">
                    @csrf
                    <select class="form-select mb-2" name="reason" required>
                        <option value="copyright">Copyright</option>
                        <option value="inappropriate">Inappropriate</option>
                        <option value="spam">Spam</option>
                        <option value="other">Other</option>
                    </select>
                    <textarea class="form-control mb-2" name="details" rows="2" placeholder="Details"></textarea>
                    <button class="btn btn-danger btn-sm" type="submit">Submit report</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-printbox-toggle-form]').forEach(function (form) {
            const toggle = form.querySelector('[data-printbox-toggle]');
            const options = form.querySelector('[data-printbox-options]');

            if (!toggle || !options) {
                return;
            }

            function syncOptions() {
                options.classList.toggle('d-none', !toggle.checked);
            }

            toggle.addEventListener('change', syncOptions);
            syncOptions();
        });

        document.querySelectorAll('form').forEach(function (form) {
            const mode = form.querySelector('[data-printbox-calc-mode]');
            const sheets = form.querySelector('[data-printbox-calc-sheets]');
            const price = form.querySelector('[data-printbox-price]');
            const printboxRates = @json($printboxRates);

            if (!mode || !sheets || !price) {
                return;
            }

            function rupiah(value) {
                return 'Rp' + new Intl.NumberFormat('id-ID').format(value);
            }

            function syncPrice() {
                const count = Math.max(1, Math.min(200, Number.parseInt(sheets.value || '1', 10)));
                const perSheet = mode.value === 'bw' && count >= 10
                    ? printboxRates.bw_bulk
                    : (mode.value === 'color' ? printboxRates.color : printboxRates.bw_low);
                price.textContent = 'Printbox sheets total: ' + rupiah(count * perSheet);
            }

            mode.addEventListener('change', syncPrice);
            sheets.addEventListener('input', syncPrice);
            syncPrice();
        });

        document.addEventListener('click', function (event) {
            const copyButton = event.target.closest('[data-copy-artwork-link]');

            if (!copyButton) {
                return;
            }

            const originalText = copyButton.textContent;
            const link = copyButton.dataset.copyArtworkLink;

            function markCopied() {
                copyButton.textContent = 'Copied';
                window.setTimeout(function () {
                    copyButton.textContent = originalText;
                }, 1200);
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(link).then(markCopied);
                return;
            }

            const input = document.createElement('input');
            input.value = link;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            input.remove();
            markCopied();
        });
    });
</script>
@endsection
