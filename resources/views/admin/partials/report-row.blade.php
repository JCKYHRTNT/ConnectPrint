<form method="POST" action="{{ route('admin.reports.resolve', ['report' => $item->id]) }}" class="cp-admin-list-row" data-cursor-item="admin-report-{{ $item->id }}">
    @csrf
    @method('PATCH')
    <div>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <strong>#{{ $item->id }} - {{ $item->artwork->name ?? 'Deleted artwork' }}</strong>
            <span class="badge {{ $item->status === 'open' ? 'text-bg-warning' : 'text-bg-secondary' }}">
                @if($item->status === 'resolved')
                    Approved
                @elseif($item->status === 'rejected' || $item->status === 'dismissed')
                    Rejected
                @else
                    Open
                @endif
            </span>
        </div>
        <div class="text-muted small">
            Reporter: {{ $item->reporter->name ?? 'Unknown' }} - {{ ucfirst($item->reason) }}
        </div>
        <div class="text-muted small">
            Reported: {{ $item->created_at?->format('Y-m-d H:i') }}
        </div>
        @if($item->details)
            <p class="small mb-0 mt-1">{{ $item->details }}</p>
        @endif
    </div>
    <div class="cp-admin-row-actions">
        <select name="status" class="form-select form-select-sm">
            <option value="resolved" @selected($item->status === 'resolved')>Approve</option>
            <option value="rejected" @selected($item->status === 'rejected' || $item->status === 'dismissed')>Reject</option>
        </select>
        <button class="btn btn-outline-secondary btn-sm" type="submit">Update</button>
    </div>
</form>
