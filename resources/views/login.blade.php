@extends('layouts.master')

@section('title', __('login.page_title'))

@section('content')
@php
    $currentLocale = request()->route('locale') ?? 'en';
@endphp
<style>
    .login-wrapper {
        display: flex;
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        max-width: 900px;
        margin: 2rem auto;
    }

    .login-left {
        flex: 1;
        background: #f4f4f5;
        padding: 3rem 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-right {
    flex: 1;
    background: var(--tb-blue);
    padding: 3rem 2rem;
    color: white;
    position: relative;
}


    .login-title {
        font-size: 1.6rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        color: #111827;
    }

    .login-card-logo {
        width: 70px;
        height: 70px;
        object-fit: cover;
        border-radius: 0.5rem;
        margin-bottom: 0.8rem;
        border: 3px solid var(--tb-yellow);
    }

    .login-input {
        width: 100%;
        border-radius: 0.75rem;
        border: none;
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .login-input:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(250,204,21,0.4);
    }

    .login-button {
        width: 100%;
        padding: 0.7rem;
        border-radius: 0.75rem;
        border: none;
        background: var(--tb-yellow);
        font-weight: 600;
        color: #111827;
        margin-top: 0.5rem;
    }

    .login-button:hover {
        background: #fbbf24;
    }

    .login-footer {
        margin-top: 1rem;
        font-size: 0.85rem;
        color: #e5e7eb;
    }

    .login-image {
        width: 90%;
        max-width: 360px;
        opacity: 0.95;
    }

    .login-label {
        font-size: 0.85rem;
        font-weight: 500;
        margin-bottom: 0.25rem;
        color: #e5e7eb;
    }
    .language-switch {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 0.8rem;
}

.language-switch a {
    color: white;
    text-decoration: none;
    font-weight: 600;
    opacity: 0.7;
    margin-left: 0.5rem;
}

.language-switch a.active {
    opacity: 1;
    text-decoration: underline;
}

</style>

<div class="login-wrapper">

    {{-- LEFT SIDE --}}
    <div class="login-left">
        <div>
            <h2 class="login-title">{{ __('login.title') }}</h2>

            <img src="{{ asset('images/placeholders/blank-artwork.svg') }}"
                 alt="Login Illustration"
                 class="login-image">
        </div>
    </div>

    {{-- RIGHT SIDE --}}
    <div class="login-right">
<div class="language-switch">
    <a href="{{ route('login.locale', ['locale' => 'en']) }}"
       class="{{ $currentLocale === 'en' ? 'active' : '' }}">
        EN
    </a>
    |
    <a href="{{ route('login.locale', ['locale' => 'id']) }}"
       class="{{ $currentLocale === 'id' ? 'active' : '' }}">
        ID
    </a>
</div>


        <div class="text-center mb-4">
            <img src="{{ asset('images/placeholders/blank-logo.svg') }}" class="login-card-logo">
            <h3 style="font-size:1.3rem;font-weight:600;margin:0;">
                {{ __('login.brand') }}
            </h3>
        </div>

        @if(session('error'))
            <div class="alert alert-danger py-2">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit', ['locale' => app()->getLocale()]) }}">

            @csrf

            <label class="login-label">{{ __('login.email') }}</label>
            <input
                type="email"
                name="email"
                class="login-input"
                placeholder="{{ __('login.email_placeholder') }}"
                value="{{ old('email') }}"
                required
            >

            <label class="login-label">{{ __('login.password') }}</label>
            <input
                type="password"
                name="password"
                class="login-input"
                placeholder="{{ __('login.password_placeholder') }}"
                required
            >

            <button type="submit" class="login-button">
                {{ __('login.login_button') }}
            </button>
        </form>

        <div class="login-footer text-center">
            {{ __('login.no_account') }}
            <a href="{{ route('register') }}" style="color:var(--tb-yellow);text-decoration:none;">
                {{ __('login.sign_up') }}
            </a>
        </div>

    </div>

</div>

@endsection
