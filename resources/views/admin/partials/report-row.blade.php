<form method="POST" action="{{ route('admin.reports.resolve', ['report' => $item->id]) }}" class="cp-admin-list-row" data-cursor-item="admin-report-{{ $item->id }}">
    @csrf
    @method('PATCH')
    <div>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <strong>#{{ $item->id }} - {{ $item->artwork->name ?? 'Deleted artwork' }}</strong>
            <span class="badge {{ $item->status === 'open' ? 'text-bg-warning' : 'text-bg-secondary' }}">
                {{ ucfirst($item->status) }}
            </span>
        </div>
        <div class="text-muted small">
            Reporter: {{ $item->reporter->name ?? 'Unknown' }} - {{ ucfirst($item->reason) }}
        </div>
        @if($item->details)
            <p class="small mb-0 mt-1">{{ $item->details }}</p>
        @endif
    </div>
    <div class="cp-admin-row-actions">
        <select name="status" class="form-select form-select-sm">
            <option value="open" @selected($item->status === 'open')>Open</option>
            <option value="resolved" @selected($item->status === 'resolved')>Resolved</option>
            <option value="dismissed" @selected($item->status === 'dismissed')>Dismissed</option>
        </select>
        <label class="form-check mb-0">
            <input class="form-check-input" type="checkbox" name="archive_artwork" value="1">
            <span class="form-check-label">Archive artwork</span>
        </label>
        <button class="btn btn-outline-secondary btn-sm" type="submit">Update</button>
    </div>
</form>
