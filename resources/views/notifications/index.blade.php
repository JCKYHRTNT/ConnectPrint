@extends('layouts.master')

@section('title', 'Notifications - ConnectPrint')

@section('content')
<div class="tb-card p-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1 style="font-size:1.4rem;font-weight:700;">Notifications</h1>
        <form method="POST" action="{{ route('notifications.read-all') }}">@csrf <button class="btn btn-outline-secondary btn-sm">Mark all read</button></form>
    </div>
    @if($notifications->count() === 0)
        <p class="text-muted">No notifications.</p>
    @else
        <div
            data-cursor-feed
            data-cursor-endpoint="{{ route('notifications.index') }}"
            data-next-cursor="{{ $notifications->nextCursor()?->encode() }}"
            data-has-more="{{ $notifications->hasMorePages() ? '1' : '0' }}"
        >
            <div data-cursor-list>
                @foreach($notifications as $notification)
                    @include('notifications.partials.notification-row', ['notification' => $notification])
                @endforeach
            </div>
            @include('partials.cursor-feed-footer')
        </div>
    @endif
</div>

@include('partials.cursor-feed-script')
@endsection
