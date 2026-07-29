<style>
    .tb-category-dropdown {
        max-height: 13rem;
        min-width: 14rem;
        overflow-y: auto;
        padding: 0.25rem 0.75rem;
    }

    .tb-category-option {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.35rem 0;
        white-space: nowrap;
    }

    .tb-header-search-form {
        gap: 0.4rem;
        min-width: 260px;
    }

    .tb-bell-icon {
        width: 16px;
        height: 16px;
        display: inline-block;
        position: relative;
        border: 1.8px solid currentColor;
        border-top-left-radius: 999px;
        border-top-right-radius: 999px;
        border-bottom: none;
        opacity: 0.85;
    }

    .tb-bell-icon::before {
        content: "";
        position: absolute;
        left: 50%;
        top: -3px;
        width: 4px;
        height: 4px;
        border-radius: 999px;
        background: currentColor;
        transform: translateX(-50%);
    }

    .tb-bell-icon::after {
        content: "";
        position: absolute;
        left: 50%;
        bottom: -5px;
        width: 6px;
        height: 6px;
        border-radius: 999px;
        border: 1.8px solid currentColor;
        border-top: none;
        transform: translateX(-50%);
    }
</style>

<header class="tb-header-fixed">
    <div class="tb-container">
        @php
            use Illuminate\Support\Str;

            $loggedIn = session('user_id') !== null;
            $userSlug = $loggedIn ? Str::slug(session('name')) : null;
            $isAdmin  = $loggedIn && session('role') === 'admin';

            $selectedCategories = collect((array) request('category'))
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (int) $id)
                ->all();
            $availableCategories = $headerCategories ?? ($categories ?? collect());

            if ($loggedIn) {
                $searchBaseUrl = route('home.user');
            } else {
                $searchBaseUrl = url('/');
            }

            if ($loggedIn && $userSlug) {
                $logoHref = route('home.user');
            } else {
                // guest
                $logoHref = route('home');
            }
        @endphp

        {{-- Logo --}}
        <a href="{{ $logoHref }}" class="d-inline-flex align-items-center" style="gap:0.5rem;">
            <img
                src="{{ asset('images/Logo ConnectPrint.png') }}"
                alt="ConnectPrint"
                style="height:42px;width:auto;object-fit:contain;"
            >
        </a>

        <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap:0.75rem;">

            {{-- LEFT: Search + Filter --}}
            <div class="d-flex flex-grow-1 align-items-center" style="gap:0.5rem;max-width:620px;min-width:260px;">

                {{-- Search --}}
                <form action="{{ $searchBaseUrl }}" method="GET" class="tb-header-search-form d-flex flex-grow-1" data-cp-navbar-search-form>
                    @foreach($selectedCategories as $selectedCategory)
                        <input type="hidden" name="category[]" value="{{ $selectedCategory }}">
                    @endforeach

                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        class="tb-input-rounded"
                        placeholder="Search artworks..."
                        style="
                            padding-left:1rem;
                            width: 480px;
                            max-width: 100%;
                        "
                    >

                    <button type="submit" class="tb-btn-primary d-inline-flex align-items-center">
                        <img
                            src="{{ asset('images/search_icon.png') }}"
                            alt="Search"
                            style="height:15px;width:15px;opacity:0.9;margin-right:0.3rem;"
                        >
                    </button>
                </form>

                {{-- Filter --}}
                @php
                    $showFilter = request()->routeIs(
                        'home',
                        'home.user',
                        'cart'
                    );

                    $clearCategoryQuery = collect(request()->except('category'))
                        ->filter(fn ($value) => $value !== null && $value !== '')
                        ->all();
                    $clearCategoryUrl = $searchBaseUrl . ($clearCategoryQuery ? '?' . http_build_query($clearCategoryQuery) : '');
                @endphp

                @if($showFilter)
                    <div class="dropdown">
                        <button
                            type="button"
                            class="tb-pill-link dropdown-toggle d-inline-flex align-items-center"
                            id="filterDropdown"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            style="border:1px solid rgba(148,163,184,0.5);white-space:nowrap;"
                        >
                            <img
                                src="{{ asset('images/filter_icon.png') }}"
                                alt="Filter"
                                style="height:16px;width:16px;opacity:0.85;margin-right:0.35rem;"
                            >
                            Categories
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end p-2" aria-labelledby="filterDropdown">
                            <li>
                                <form method="GET" action="{{ $searchBaseUrl }}">
                                    @if(request('q'))
                                        <input type="hidden" name="q" value="{{ request('q') }}">
                                    @endif

                                    <div class="tb-category-dropdown">
                                        @foreach($availableCategories as $cat)
                                            <label class="tb-category-option">
                                                <input
                                                    class="form-check-input m-0"
                                                    type="checkbox"
                                                    name="category[]"
                                                    value="{{ $cat->id }}"
                                                    @checked(in_array((int) $cat->id, $selectedCategories, true))
                                                >
                                                <span>{{ ucfirst($cat->name) }}</span>
                                            </label>
                                        @endforeach
                                    </div>

                                    <div class="d-flex gap-2 pt-2">
                                        <button class="btn btn-primary btn-sm" type="submit">Apply</button>
                                        <a class="btn btn-outline-secondary btn-sm" href="{{ $clearCategoryUrl }}">Clear</a>
                                    </div>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endif
            </div>

            {{-- RIGHT: Navbar --}}
            <nav class="d-flex flex-wrap align-items-center justify-content-end" style="gap:0.4rem;">

                {{-- HOME --}}
                @if($loggedIn)
                    <a href="{{ route('home.user') }}"
                    class="tb-pill-link d-inline-flex align-items-center"
                    style="gap:0.35rem;">
                        <img src="{{ asset('images/home_icon.png') }}" alt="Home" style="height:16px;width:16px;opacity:0.85;">
                        Home
                    </a>
                @else
                    <a href="{{ route('home') }}"
                    class="tb-pill-link d-inline-flex align-items-center"
                    style="gap:0.35rem;">
                        <img src="{{ asset('images/home_icon.png') }}" alt="Home" style="height:16px;width:16px;opacity:0.85;">
                        Home
                    </a>
                @endif

                {{-- CART --}}
                @if($loggedIn)
                    <a href="{{ route('cart') }}"
                    class="tb-pill-link d-inline-flex align-items-center"
                    style="gap:0.35rem;">
                        <img src="{{ asset('images/cart_icon.png') }}" alt="Cart" style="height:16px;width:16px;opacity:0.85;">
                        Cart
                    </a>
                @elseif(!$loggedIn)
                    <a href="{{ route('cart') }}"
                    class="tb-pill-link d-inline-flex align-items-center"
                    style="gap:0.35rem;">
                        <img src="{{ asset('images/cart_icon.png') }}" alt="Cart" style="height:16px;width:16px;opacity:0.85;">
                        Cart
                    </a>
                @endif

                @if($isAdmin)
                    <a href="{{ route('admin.crud.short') }}"
                    class="tb-pill-link d-inline-flex align-items-center"
                    style="gap:0.35rem;">
                        Admin
                    </a>
                @endif

                {{-- ACCOUNT / LOGIN --}}
                @if($loggedIn)
                    <a href="{{ route('account') }}"
                    class="tb-pill-link d-inline-flex align-items-center"
                    style="gap:0.35rem;">
                        <img src="{{ asset('images/account_icon.png') }}" alt="Account" style="height:16px;width:16px;opacity:0.85;">
                        Profile
                    </a>
                @else
                    <a href="{{ route('login') }}"
                    class="tb-pill-link d-inline-flex align-items-center"
                    style="gap:0.35rem;">
                        <img src="{{ asset('images/account_icon.png') }}" alt="Login" style="height:16px;width:16px;opacity:0.85;">
                        Login
                    </a>
                @endif

                @if($loggedIn)
                    <a href="{{ route('notifications.index') }}"
                    class="tb-pill-link d-inline-flex align-items-center"
                    style="gap:0.35rem;">
                        <span class="tb-bell-icon" aria-hidden="true"></span>
                    </a>
                @endif
            </nav>
        </div>
    </div>
</header>
