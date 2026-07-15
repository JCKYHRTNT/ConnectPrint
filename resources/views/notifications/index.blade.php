@extends('layouts.master')

@section('title', 'Notifications - ConnectPrint')

@php use Illuminate\Support\Str; @endphp

@section('content')
<div class="tb-card p-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1 style="font-size:1.4rem;font-weight:700;">Notifications</h1>
        <form method="POST" action="{{ route('notifications.read-all', ['username' => Str::slug(session('name'))]) }}">@csrf <button class="btn btn-outline-secondary btn-sm">Mark all read</button></form>
    </div>
    @forelse($notifications as $notification)
        <div class="border rounded p-3 mb-2 {{ $notification->read_at ? '' : 'bg-light' }}">
            <div>{{ $notification->message }}</div>
            <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
            @if(! $notification->read_at)
                <form method="POST" action="{{ route('notifications.read', ['username' => Str::slug(session('name')), 'notification' => $notification->id]) }}" class="mt-1">@csrf @method('PATCH')<button class="btn btn-outline-primary btn-sm">Mark read</button></form>
            @endif
        </div>
    @empty
        <p class="text-muted">No notifications.</p>
    @endforelse
    {{ $notifications->links() }}
</div>
@endsection
