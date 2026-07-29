@extends('layouts.master')

@section('title', 'Admin Management - ConnectPrint')

@php
    $adminTabs = [
        'admins' => 'Admin Management',
        'categories' => 'Category Management',
        'reports' => 'Reports',
        'fees' => 'Fee Settings',
    ];
    $activeAdminTab = $activeAdminTab ?? request('admin_tab', 'admins');
    if (! array_key_exists($activeAdminTab, $adminTabs)) {
        $activeAdminTab = 'admins';
    }

    $adminBaseRoute = route('admin.crud');
    $adminUserSearch = $adminUserSearch ?? request('admin_user_q');
    $adminCategorySearch = $adminCategorySearch ?? request('admin_category_q');
    $usersEndpoint = $adminBaseRoute . '?' . http_build_query(array_filter([
        'admin_tab' => 'admins',
        'admin_user_q' => $adminUserSearch,
    ], fn ($value) => $value !== null && $value !== ''));
    $categoriesEndpoint = $adminBaseRoute . '?' . http_build_query(array_filter([
        'admin_tab' => 'categories',
        'admin_category_q' => $adminCategorySearch,
    ], fn ($value) => $value !== null && $value !== ''));
    $reportStatus = $reportStatus ?? request('report_status');
    if (! in_array($reportStatus, ['open', 'closed'], true)) {
        $reportStatus = null;
    }
    $reportsEndpoint = $adminBaseRoute . '?' . http_build_query(array_filter([
        'admin_tab' => 'reports',
        'report_status' => $reportStatus,
    ]));
@endphp

@section('content')
<style>
    .cp-admin-nav {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        width: fit-content;
        max-width: 100%;
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 0.7rem;
        background: #ffffff;
        padding: 0.25rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }

    .cp-admin-nav-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.2rem;
        padding: 0.5rem 0.85rem;
        border-radius: 0.5rem;
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 500;
        line-height: 1;
        white-space: nowrap;
    }

    .cp-admin-nav-link:hover {
        color: #0f172a;
        background: #f8fafc;
    }

    .cp-admin-nav-link.is-active {
        color: #0f172a;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(226, 232, 240, 0.9);
    }

    .cp-admin-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .cp-admin-stat,
    .cp-admin-panel {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #ffffff;
    }

    .cp-admin-stat {
        padding: 0.9rem;
    }

    .cp-admin-stat strong {
        display: block;
        font-size: 1.4rem;
        line-height: 1;
    }

    .cp-admin-actions,
    .cp-admin-row-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }

    .cp-admin-row-actions {
        justify-content: flex-end;
    }

    .cp-admin-list-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.85rem;
        background: #ffffff;
        margin-bottom: 0.6rem;
    }

    .cp-admin-list-row .form-select {
        min-width: 8.5rem;
    }

    .cp-admin-fee-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
        align-items: end;
    }

    .cp-admin-filter-row {
        display: grid;
        grid-template-columns: minmax(240px, 1fr) auto;
        gap: 0.75rem;
        align-items: end;
    }

    @media (max-width: 900px) {
        .cp-admin-stat-grid,
        .cp-admin-fee-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .cp-admin-nav {
            width: 100%;
        }

        .cp-admin-stat-grid,
        .cp-admin-fee-grid {
            grid-template-columns: 1fr;
        }

        .cp-admin-list-row,
        .cp-admin-row-actions,
        .cp-admin-filter-row {
            display: block;
        }

        .cp-admin-row-actions > * {
            margin-top: 0.5rem;
            width: 100%;
        }
    }
</style>

@if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif
@if(session('status'))
    <div class="alert alert-success py-2">{{ session('status') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger py-2">{{ session('error') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
    <h1 style="font-size:1.65rem;font-weight:700;margin:0;">Admin</h1>

    <nav class="cp-admin-nav" aria-label="Admin navigation">
        @foreach($adminTabs as $tabKey => $tabLabel)
            <a
                href="{{ route('admin.crud', ['admin_tab' => $tabKey]) }}"
                class="cp-admin-nav-link {{ $activeAdminTab === $tabKey ? 'is-active' : '' }}"
                @if($activeAdminTab === $tabKey) aria-current="page" @endif
            >
                {{ $tabLabel }}
            </a>
        @endforeach
    </nav>
</div>

@if($activeAdminTab === 'admins')
    <section class="cp-admin-panel p-4 mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 style="font-size:1.25rem;font-weight:700;margin:0;">Admin Management</h2>
            <div class="cp-admin-actions">
                <button
                    type="button"
                    class="btn btn-outline-primary btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#modalCreateAdmin"
                    @if(empty($eligibleUsers) || collect($eligibleUsers)->isEmpty()) disabled @endif
                >
                    Promote Admin
                </button>
                <button
                    type="button"
                    class="btn btn-outline-danger btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#modalDemoteAdmin"
                    @if(empty($demotableAdmins) || collect($demotableAdmins)->isEmpty()) disabled @endif
                >
                    Demote Admin
                </button>
            </div>
        </div>

        <div class="cp-admin-stat-grid mb-3">
            <div class="cp-admin-stat">
                <span class="text-muted small">Admins</span>
                <strong>{{ $adminCount }}</strong>
            </div>
            <div class="cp-admin-stat">
                <span class="text-muted small">Users</span>
                <strong>{{ $userCount }}</strong>
            </div>
            <div class="cp-admin-stat">
                <span class="text-muted small">Suspended</span>
                <strong>{{ $suspendedCount }}</strong>
            </div>
            <div class="cp-admin-stat">
                <span class="text-muted small">Open reports</span>
                <strong>{{ $openReportCount }}</strong>
            </div>
        </div>

        <h3 class="mb-2" style="font-size:1rem;font-weight:700;">Users</h3>
        <form method="GET" action="{{ route('admin.crud') }}" class="cp-admin-filter-row mb-3" data-admin-filter-form>
            <input type="hidden" name="admin_tab" value="admins">
            <div>
                <label class="form-label" for="admin_user_q">Search</label>
                <input
                    type="search"
                    id="admin_user_q"
                    name="admin_user_q"
                    class="form-control"
                    value="{{ $adminUserSearch }}"
                    placeholder="ID, name, or email"
                    data-admin-filter-input
                >
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('admin.crud', ['admin_tab' => 'admins']) }}">Clear</a>
        </form>
        <div
            data-cursor-feed
            data-cursor-endpoint="{{ $usersEndpoint }}"
            data-next-cursor="{{ $users->nextCursor()?->encode() }}"
            data-has-more="{{ $users->hasMorePages() ? '1' : '0' }}"
        >
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:80px;">ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th style="width:150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody data-cursor-list>
                        @foreach($users as $item)
                            @include('admin.partials.user-row', [
                                'item' => $item,
                                'currentAdminId' => session('user_id'),
                            ])
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('partials.cursor-feed-footer')
        </div>
    </section>
@elseif($activeAdminTab === 'categories')
    <section class="cp-admin-panel p-4 mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 style="font-size:1.25rem;font-weight:700;margin:0;">Category Management</h2>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateCategory">
                Add Category
            </button>
        </div>

        <div class="cp-admin-stat-grid mb-3">
            <div class="cp-admin-stat">
                <span class="text-muted small">Categories</span>
                <strong>{{ $categoryCount }}</strong>
            </div>
            <div class="cp-admin-stat">
                <span class="text-muted small">Artworks</span>
                <strong>{{ $artworkCount }}</strong>
            </div>
        </div>

        <h3 class="mb-2" style="font-size:1rem;font-weight:700;">Categories</h3>
        <form method="GET" action="{{ route('admin.crud') }}" class="cp-admin-filter-row mb-3" data-admin-filter-form>
            <input type="hidden" name="admin_tab" value="categories">
            <div>
                <label class="form-label" for="admin_category_q">Search</label>
                <input
                    type="search"
                    id="admin_category_q"
                    name="admin_category_q"
                    class="form-control"
                    value="{{ $adminCategorySearch }}"
                    placeholder="ID or category name"
                    data-admin-filter-input
                >
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('admin.crud', ['admin_tab' => 'categories']) }}">Clear</a>
        </form>
        <div
            data-cursor-feed
            data-cursor-endpoint="{{ $categoriesEndpoint }}"
            data-next-cursor="{{ $categories->nextCursor()?->encode() }}"
            data-has-more="{{ $categories->hasMorePages() ? '1' : '0' }}"
        >
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:80px;">ID</th>
                            <th>Name</th>
                            <th style="width:180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody data-cursor-list>
                        @foreach($categories as $item)
                            @include('admin.partials.category-row', ['item' => $item])
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('partials.cursor-feed-footer')
        </div>
    </section>
@elseif($activeAdminTab === 'reports')
    <section class="cp-admin-panel p-4 mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 style="font-size:1.25rem;font-weight:700;margin:0;">Reports</h2>
            <span class="badge text-bg-secondary">{{ $openReportCount }} open</span>
        </div>

        <div class="cp-admin-actions mb-3" aria-label="Report status filter">
            <a
                class="btn btn-sm {{ $reportStatus === null ? 'btn-primary' : 'btn-outline-secondary' }}"
                href="{{ route('admin.crud', ['admin_tab' => 'reports']) }}"
            >
                All
            </a>
            <a
                class="btn btn-sm {{ $reportStatus === 'open' ? 'btn-primary' : 'btn-outline-secondary' }}"
                href="{{ route('admin.crud', ['admin_tab' => 'reports', 'report_status' => 'open']) }}"
            >
                Open
            </a>
            <a
                class="btn btn-sm {{ $reportStatus === 'closed' ? 'btn-primary' : 'btn-outline-secondary' }}"
                href="{{ route('admin.crud', ['admin_tab' => 'reports', 'report_status' => 'closed']) }}"
            >
                Closed
            </a>
        </div>

        @if($reports->count() === 0)
            <p class="mb-0 text-muted">No reports found.</p>
        @else
            <div
                data-cursor-feed
                data-cursor-endpoint="{{ $reportsEndpoint }}"
                data-next-cursor="{{ $reports->nextCursor()?->encode() }}"
                data-has-more="{{ $reports->hasMorePages() ? '1' : '0' }}"
            >
                <div data-cursor-list>
                    @foreach($reports as $item)
                        @include('admin.partials.report-row', ['item' => $item])
                    @endforeach
                </div>
                @include('partials.cursor-feed-footer')
            </div>
        @endif
    </section>
@else
    <section class="cp-admin-panel p-4 mb-3">
        <h2 class="mb-3" style="font-size:1.25rem;font-weight:700;">Fee Settings</h2>

        <form method="POST" action="{{ route('admin.fees.update') }}" class="cp-admin-fee-grid">
            @csrf
            <div>
                <label class="form-label" for="application_fee">Application fee (Rp)</label>
                <input type="number" id="application_fee" name="application_fee" class="form-control" min="0" value="{{ old('application_fee', $applicationFee ?? 0) }}" required>
            </div>
            <div>
                <label class="form-label" for="printbox_bw_low_fee">BW 0-9 (Rp/sheet)</label>
                <input type="number" id="printbox_bw_low_fee" name="printbox_bw_low_fee" class="form-control" min="0" value="{{ old('printbox_bw_low_fee', $printboxRates['bw_low'] ?? 750) }}" required>
            </div>
            <div>
                <label class="form-label" for="printbox_bw_bulk_fee">BW &gt;10 (Rp/sheet)</label>
                <input type="number" id="printbox_bw_bulk_fee" name="printbox_bw_bulk_fee" class="form-control" min="0" value="{{ old('printbox_bw_bulk_fee', $printboxRates['bw_bulk'] ?? 500) }}" required>
            </div>
            <div>
                <label class="form-label" for="printbox_color_fee">Color (Rp/sheet)</label>
                <input type="number" id="printbox_color_fee" name="printbox_color_fee" class="form-control" min="0" value="{{ old('printbox_color_fee', $printboxRates['color'] ?? 750) }}" required>
            </div>
            <div>
                <button class="btn btn-primary btn-sm" type="submit">Save Fees</button>
            </div>
        </form>
    </section>
@endif

<div class="modal fade" id="modalCreateCategory" tabindex="-1" aria-labelledby="modalCreateCategoryLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.categories.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCreateCategoryLabel">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="cat_new_name">Name</label>
                    <input type="text" id="cat_new_name" name="name" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
                    <label class="form-label" for="cat_edit_name">Name</label>
                    <input type="text" id="cat_edit_name" name="name" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
                    <p id="deleteCategoryText" class="mb-0"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCreateAdmin" tabindex="-1" aria-labelledby="modalCreateAdminLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.crud.promote') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCreateAdminLabel">Promote Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label" for="admin_user_id">User</label>
                        <select id="admin_user_id" name="user_id" class="form-select" required>
                            <option value="">Choose a user</option>
                            @foreach($eligibleUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="admin_current_password">Your password</label>
                        <input type="password" id="admin_current_password" name="current_password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Promote</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDemoteAdmin" tabindex="-1" aria-labelledby="modalDemoteAdminLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.crud.demote') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDemoteAdminLabel">Demote Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label" for="demote_user_id">Admin</label>
                        <select id="demote_user_id" name="user_id" class="form-select" required>
                            <option value="">Choose an admin</option>
                            @foreach($demotableAdmins as $admin)
                                <option value="{{ $admin->id }}">{{ $admin->name }} ({{ $admin->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="demote_current_password">Your password</label>
                        <input type="password" id="demote_current_password" name="current_password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Demote</button>
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
                const form = document.getElementById('formEditCategory');
                const input = document.getElementById('cat_edit_name');

                form.action = "{{ url('/admin/categories') }}/" + button.getAttribute('data-id');
                input.value = button.getAttribute('data-name');
            });
        }

        const deleteModal = document.getElementById('modalDeleteCategory');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const form = document.getElementById('formDeleteCategory');
                const text = document.getElementById('deleteCategoryText');

                form.action = "{{ url('/admin/categories') }}/" + button.getAttribute('data-id');
                text.textContent = 'Delete category "' + button.getAttribute('data-name') + '"?';
            });
        }

        document.querySelectorAll('[data-admin-filter-form]').forEach(function (form) {
            let timer = null;

            form.querySelectorAll('[data-admin-filter-input]').forEach(function (input) {
                input.addEventListener('input', function () {
                    window.clearTimeout(timer);
                    timer = window.setTimeout(function () {
                        form.requestSubmit();
                    }, 400);
                });
            });
        });
    });
</script>

@if(in_array($activeAdminTab, ['admins', 'categories', 'reports'], true))
    @include('partials.cursor-feed-script')
@endif
@endsection
