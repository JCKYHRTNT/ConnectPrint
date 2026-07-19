<div class="border rounded p-3 mb-2 {{ $notification->read_at ? '' : 'bg-light' }}" data-cursor-item="notification-{{ $notification->id }}">
    <div>{{ $notification->message }}</div>
    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
    @if(! $notification->read_at)
        <form method="POST" action="{{ route('notifications.read', ['notification' => $notification->id]) }}" class="mt-1">
            @csrf
            @method('PATCH')
            <button class="btn btn-outline-primary btn-sm">Mark read</button>
        </form>
    @endif
</div>
