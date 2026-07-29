<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'ConnectPrint')</title>

    <link rel="icon" type="image/png" href="{{ asset('images/Icon ConnectPrint.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --tb-black: #020617;
            --tb-blue: #0f766e;
            --tb-yellow: #facc15;
            --tb-gray-bg: #f4f4f5;
            --tb-gray-border: #e5e7eb;
            --tb-gray-text: #6b7280;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Figtree, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: var(--tb-gray-bg);
            color: #111827;
        }

        a { text-decoration: none; color: inherit; }

        .tb-container {
            max-width: 1120px;
            margin: 0 auto;
            padding: 1rem 1.25rem;
        }

        .tb-card {
            background: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid rgba(148,163,184,0.3);
            box-shadow: 0 10px 30px rgba(15,23,42,0.12);
        }

        .tb-btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--tb-blue);
            color: #ffffff;
            border-radius: 999px;
            border: none;
            padding: 0.4rem 0.9rem;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
        }

        .tb-btn-primary:hover { filter: brightness(1.1); }

        .tb-pill-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #e5e7eb;
            background: transparent;
        }

        .tb-pill-link:hover {
            background: #111827;
            color: #38bdf8;
        }

        .tb-input-rounded {
            width: 100%;
            border-radius: 999px;
            border: 1px solid #9ca3af;
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
        }

        .tb-input-rounded:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59,130,246,0.25);
        }

        .cp-cursor-footer {
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: var(--tb-gray-text);
            font-size: 0.9rem;
        }

        .cp-cursor-sentinel {
            width: 100%;
            height: 2.5rem;
        }

        .cp-cursor-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 2rem;
        }

        .cp-return-top {
            position: fixed;
            left: 50%;
            bottom: 4.5rem;
            transform: translateX(-50%);
            z-index: 1050;
            border-radius: 999px;
            background: rgba(255,255,255,0.96);
            box-shadow: 0 10px 30px rgba(15,23,42,0.18);
        }

        .cp-artwork-frame {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            background: #f8fafc;
            border-bottom: 1px solid var(--tb-gray-border);
            overflow: hidden;
        }

        .cp-artwork-frame-card {
            aspect-ratio: 4 / 3;
        }

        .cp-artwork-frame-detail {
            height: min(72vh, 42rem);
            min-height: 22rem;
            border: 1px solid var(--tb-gray-border);
            border-radius: 8px;
        }

        .cp-artwork-image-contained {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .tb-footer {
            border-top: 1px solid var(--tb-gray-border);
            padding: 1.5rem 0 1rem;
            font-size: 0.8rem;
            color: var(--tb-gray-text);
            text-align: center;
            background: #f9fafb;
        }

        .tb-header-fixed {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: var(--tb-black);
            color: #f9fafb;
        }

        .tb-main {
            padding-top: 8rem;
            padding-bottom: 2rem;
        }

        @media (min-width: 768px) {
            .tb-flex-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
        }

        @media (max-width: 575.98px) {
            .cp-artwork-frame-detail {
                height: min(64vh, 28rem);
                min-height: 16rem;
            }
        }
    </style>

    @stack('styles')
</head>
<body>

    @include('layouts.header')

    <main class="tb-container tb-main">
        @yield('content')
    </main>

    @include('layouts.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.tb-add-to-cart-form').forEach(function (form) {
                var inactive = form.querySelector('.tb-add-to-cart-inactive');
                var active   = form.querySelector('.tb-add-to-cart-active');
                var trigger  = form.querySelector('.tb-add-to-cart-trigger');
                var qtyInput = form.querySelector('input[name="quantity"]');
                var qtyDisplay = form.querySelector('.tb-qty-display');
                var minusBtn = form.querySelector('[data-role="qty-minus"]');
                var plusBtn  = form.querySelector('[data-role="qty-plus"]');
                var max = parseInt(form.getAttribute('data-max'), 10);
                if (isNaN(max) || max < 1) {
                    max = 1;
                }

                if (!trigger || !inactive || !active || !qtyInput || !qtyDisplay || !minusBtn || !plusBtn) {
                    return;
                }

                // First click: switch layout, do NOT submit
                trigger.addEventListener('click', function (e) {
                    e.preventDefault();
                    inactive.classList.add('d-none');
                    active.classList.remove('d-none');
                });

                // Helpers
                function syncQty(val) {
                    if (val < 1) val = 1;
                    if (val > max) val = max;
                    qtyInput.value   = val;
                    qtyDisplay.textContent = val;
                }

                // - button
                minusBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var val = parseInt(qtyInput.value, 10) || 1;
                    syncQty(val - 1);
                });

                // + button
                plusBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var val = parseInt(qtyInput.value, 10) || 1;
                    syncQty(val + 1);
                });

                // Initialize
                syncQty(parseInt(qtyInput.value, 10) || 1);
            });
        });
        </script>

</body>
</html>
