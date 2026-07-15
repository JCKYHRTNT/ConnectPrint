@extends('layouts.master')

@section('title', 'Admin - ConnectPrint')

@php
    use Illuminate\Support\Str;
    $adminSlug = Str::slug(session('name'));
@endphp

@section('content')

<style>
    .tb-btn-secondary {
        display:inline-flex;align-items:center;justify-content:center;
        background:var(--tb-blue);color:#fff;border-radius:999px;
        padding:0.4rem 0.9rem;font-size:0.85rem;font-weight:500;
        border:none;cursor:pointer;text-decoration:none;
    }
    .tb-btn-secondary:hover { filter:brightness(1.12); }

    .tb-btn-danger {
        display:inline-flex;align-items:center;justify-content:center;
        background:#dc2626;color:#fff;border-radius:999px;
        padding:0.4rem 0.9rem;font-size:0.85rem;font-weight:500;
        border:none;cursor:pointer;text-decoration:none;
    }
    .tb-btn-danger:hover { background:#ef4444; }
</style>

<div class="tb-card p-4 p-md-5 mb-3">
    <h1 style="font-size:1.6rem;font-weight:600;margin-bottom:1rem;">
        Admin Management
    </h1>

    {{-- FLASH --}}
    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if(session('status'))
        <div class="alert alert-success py-2">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    {{-- STATS --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="tb-card p-3 h-100">
                <div style="font-size:0.8rem;color:var(--tb-gray-text);">Artworks</div>
                <div style="font-size:1.4rem;font-weight:700;">{{ $artworkCount }}</div>
                <a href="{{ route('admin.user', ['username' => $adminSlug]) }}"
                   class="tb-btn-secondary mt-2" style="font-size:0.8rem;">
                    Go to Artwork Home
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="tb-card p-3 h-100">
                <div style="font-size:0.8rem;color:var(--tb-gray-text);">Categories</div>
                <div style="font-size:1.4rem;font-weight:700;">{{ $categoryCount }}</div>
                <button type="button"
                        class="tb-btn-secondary mt-2"
                        style="font-size:0.8rem;"
                        data-bs-toggle="modal"
                        data-bs-target="#modalCreateCategory">
                    + New Category
                </button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="tb-card p-3 h-100">
                <div style="font-size:0.8rem;color:var(--tb-gray-text);">Admins</div>
                <div style="font-size:1.4rem;font-weight:700;">{{ $adminCount }}</div>

                {{-- Promote to admin --}}
                <button type="button"
                    class="tb-btn-secondary mt-2"
                    style="font-size:0.8rem;"
                    data-bs-toggle="modal"
                    data-bs-target="#modalCreateAdmin"
                    @if(empty($eligibleUsers) || collect($eligibleUsers)->isEmpty()) disabled @endif>
                + Promote Admin
            </button>

                {{-- Demote admin --}}
                <button type="button"
                        class="tb-btn-danger mt-2"
                        style="font-size:0.8rem;"
                        data-bs-toggle="modal"
                        data-bs-target="#modalDemoteAdmin"
                        @if(empty($demotableAdmins) || collect($demotableAdmins)->isEmpty()) disabled @endif>
                    Demote Admin
                </button>
            </div>
        </div>
    </div>

    {{-- CATEGORY LIST --}}
    <h2 style="font-size:1rem;font-weight:600;margin-bottom:0.5rem;">Pending artwork moderation</h2>
    <div class="tb-card p-3 mb-4">
        @if(($pendingArtworks ?? collect())->isEmpty())
            <p class="mb-0 text-muted">No pending artworks.</p>
        @else
            @foreach($pendingArtworks as $artwork)
                <form method="POST" action="{{ route('admin.artworks.moderate', ['username' => $adminSlug, 'artwork' => $artwork->id]) }}" class="border rounded p-3 mb-2">
                    @csrf
                    @method('PATCH')
                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                        <div>
                            <strong>{{ $artwork->name }}</strong>
                            <div class="text-muted small">Creator: {{ $artwork->user->name ?? 'Unknown' }}</div>
                        </div>
                        <div class="d-flex gap-2">
                            <select name="moderation_status" class="form-select form-select-sm">
                                <option value="approved">Approve</option>
                                <option value="rejected">Reject</option>
                            </select>
                            <input class="form-control form-control-sm" name="moderation_reason" placeholder="Reason">
                            <button class="tb-btn-secondary" type="submit">Save</button>
                        </div>
                    </div>
                </form>
            @endforeach
        @endif
    </div>

    <h2 style="font-size:1rem;font-weight:600;margin-bottom:0.5rem;">Open artwork reports</h2>
    <div class="tb-card p-3 mb-4">
        @if(($reports ?? collect())->isEmpty())
            <p class="mb-0 text-muted">No open reports.</p>
        @else
            @foreach($reports as $report)
                <form method="POST" action="{{ route('admin.reports.resolve', ['username' => $adminSlug, 'report' => $report->id]) }}" class="border rounded p-3 mb-2">
                    @csrf
                    @method('PATCH')
                    <strong>{{ $report->artwork->name ?? 'Deleted artwork' }}</strong>
                    <div class="text-muted small">Reporter: {{ $report->reporter->name ?? 'Unknown' }} - {{ ucfirst($report->reason) }}</div>
                    <p class="small mb-2">{{ $report->details }}</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <select name="status" class="form-select form-select-sm" style="max-width:160px;">
                            <option value="resolved">Resolved</option>
                            <option value="dismissed">Dismissed</option>
                        </select>
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="archive_artwork" value="1">
                            <span class="form-check-label">Archive artwork</span>
                        </label>
                        <button class="tb-btn-secondary" type="submit">Update report</button>
                    </div>
                </form>
            @endforeach
        @endif
    </div>

    {{-- CATEGORY LIST --}}
    <h2 style="font-size:1rem;font-weight:600;margin-bottom:0.5rem;">Categories</h2>
    <div class="tb-card p-3 mb-4">
        @if($categories->isEmpty())
            <p style="font-size:0.9rem;color:var(--tb-gray-text);margin:0;">
                No categories yet.
            </p>
        @else
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th>Name</th>
                        <th style="width:160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $cat)
                        <tr>
                            <td>{{ $cat->id }}</td>
                            <td>{{ $cat->name }}</td>
                            <td>
                                <div class="d-flex" style="gap:0.35rem;">
                                    <button type="button"
                                            class="tb-btn-secondary"
                                            style="font-size:0.75rem;padding:0.25rem 0.7rem;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditCategory"
                                            data-id="{{ $cat->id }}"
                                            data-name="{{ $cat->name }}">
                                        Edit
                                    </button>

                                    <button type="button"
                                            class="tb-btn-danger"
                                            style="font-size:0.75rem;padding:0.25rem 0.7rem;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalDeleteCategory"
                                            data-id="{{ $cat->id }}"
                                            data-name="{{ $cat->name }}">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>

{{-- CREATE CATEGORY MODAL --}}
<div class="modal fade" id="modalCreateCategory" tabindex="-1" aria-labelledby="modalCreateCategoryLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.categories.store', ['username' => $adminSlug]) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCreateCategoryLabel">New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label" for="cat_new_name">Name</label>
                        <input type="text" id="cat_new_name" name="name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="tb-btn-secondary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EDIT CATEGORY MODAL --}}
<div class="modal fade" id="modalEditCategory" tabindex="-1" aria-labelledby="modalEditCategoryLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEditCategory">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditCategoryLabel">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label" for="cat_edit_name">Name</label>
                        <input type="text" id="cat_edit_name" name="name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="tb-btn-secondary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- DELETE CATEGORY MODAL --}}
<div class="modal fade" id="modalDeleteCategory" tabindex="-1" aria-labelledby="modalDeleteCategoryLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formDeleteCategory">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDeleteCategoryLabel">Delete Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteCategoryText" style="margin:0;font-size:0.9rem;"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="tb-btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- PROMOTE ADMIN MODAL --}}
<div class="modal fade" id="modalCreateAdmin" tabindex="-1" aria-labelledby="modalCreateAdminLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.crud.promote', ['username' => $adminSlug]) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCreateAdminLabel">Promote User to Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-2">
                        <label class="form-label" for="admin_user_id">Select User</label>
                        <select id="admin_user_id" name="user_id" class="form-select" required>
                            <option value="">— Choose a user —</option>
                            @foreach($eligibleUsers as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label" for="admin_current_password">Your Password</label>
                        <input type="password" id="admin_current_password" name="current_password" class="form-control" required>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="tb-btn-secondary">Promote to Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- DEMOTE ADMIN MODAL --}}
<div class="modal fade" id="modalDemoteAdmin" tabindex="-1" aria-labelledby="modalDemoteAdminLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.crud.demote', ['username' => $adminSlug]) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDemoteAdminLabel">Demote Admin to User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-2">
                        <label class="form-label" for="demote_user_id">Select Admin</label>
                        <select id="demote_user_id" name="user_id" class="form-select" required>
                            <option value="">— Choose an admin —</option>
                            @foreach($demotableAdmins as $admin)
                                <option value="{{ $admin->id }}">
                                    {{ $admin->name }} ({{ $admin->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label" for="demote_current_password">Your Password</label>
                        <input type="password" id="demote_current_password" name="current_password" class="form-control" required>
                    </div>

                    <p class="mt-2 mb-0" style="font-size:0.8rem;color:var(--tb-gray-text);">
                        The selected admin will be changed back to a regular user.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="tb-btn-danger">Demote</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editModal = document.getElementById('modalEditCategory');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id     = button.getAttribute('data-id');
                const name   = button.getAttribute('data-name');

                const form   = document.getElementById('formEditCategory');
                const input  = document.getElementById('cat_edit_name');

                form.action = "{{ url('/a/'.$adminSlug.'/categories') }}/" + id;
                input.value = name;
            });
        }

        const deleteModal = document.getElementById('modalDeleteCategory');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id     = button.getAttribute('data-id');
                const name   = button.getAttribute('data-name');

                const form   = document.getElementById('formDeleteCategory');
                const text   = document.getElementById('deleteCategoryText');

                form.action = "{{ url('/a/'.$adminSlug.'/categories') }}/" + id;
                text.textContent = "Delete category \"" + name + "\"?";
            });
        }
    });
</script>

@endsection
