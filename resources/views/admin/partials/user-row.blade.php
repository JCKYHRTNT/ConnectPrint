<tr data-cursor-item="admin-user-{{ $item->id }}">
    <td>{{ $item->id }}</td>
    <td>{{ $item->name }}</td>
    <td>{{ $item->email }}</td>
    <td>
        <span class="badge {{ $item->role === 'admin' ? 'text-bg-primary' : 'text-bg-secondary' }}">
            {{ ucfirst($item->role) }}
        </span>
    </td>
    <td>
        @if($item->suspended_at)
            <span class="badge text-bg-danger">Suspended</span>
        @else
            <span class="badge text-bg-success">Active</span>
        @endif
    </td>
    <td>
        @if($item->role !== 'admin')
            @if($item->suspended_at)
                <form method="POST" action="{{ route('admin.users.unsuspend', ['username' => $adminSlug, 'user' => $item->id]) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-outline-success btn-sm" type="submit">Unsuspend</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.suspend', ['username' => $adminSlug, 'user' => $item->id]) }}" onsubmit="return confirm('Suspend this user?');">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-outline-danger btn-sm" type="submit">Suspend</button>
                </form>
            @endif
        @else
            <span class="text-muted small">Protected</span>
        @endif
    </td>
</tr>
