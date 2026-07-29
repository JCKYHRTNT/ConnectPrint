<?php

namespace App\Http\Controllers;

use App\Models\Product as Artwork;
use App\Models\Category;
use App\Models\User;
use App\Models\ArtworkReport;
use App\Models\AppNotification;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Admin dashboard (artwork list).
     */
    public function index(Request $request)
    {
        $categoryIds = collect((array) $request->input('category', []))
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $categoryId = $categoryIds->first();

        $query = $request->input('q');

        // Categories
        $categories    = Category::orderBy('name')->get();
        $categoryNames = $categories->pluck('name', 'id')->toArray();

        // Base artwork query
        $artworksQuery = Artwork::with(['category', 'user']);

        if ($categoryIds->isNotEmpty()) {
            $artworksQuery->whereIn('category_id', $categoryIds->all());
        }

        if ($query) {
            $q = strtolower($query);
            $artworksQuery->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"]);
        }

        $artworks = $artworksQuery->orderBy('id')->get();

        // Recent categories (max 3)
        $recentCategories = $categories;

        if (!is_null($categoryId) && $categories->pluck('id')->contains($categoryId)) {
            $selected = $categories->firstWhere('id', $categoryId);

            $recentCategories = collect([$selected])->merge(
                $categories->where('id', '!=', $categoryId)
            );
        }

        $recentCategories = $recentCategories
            ->take(3)
            ->map(fn ($cat) => ['id' => $cat->id, 'name' => $cat->name])
            ->values()
            ->all();

        return view('admin.home', [
            'artworks'         => $artworks,
            'categories'       => $categories,
            'categoryId'       => $categoryId,
            'query'            => $query,
            'recentCategories' => $recentCategories,
            'categoryNames'    => $categoryNames,
        ]);
    }

    /**
     * /a/{username}
     */
    public function indexForUser(Request $request, string $username)
    {
        if (!session('user_id')) {
            return redirect()->route('login');
        }

        $admin        = User::findOrFail(session('user_id'));
        $expectedSlug = $admin->slug;

        if ($username !== $expectedSlug) {
            return redirect()->route('admin.user', [
                'username' => $expectedSlug,
            ] + $request->query());
        }

        return $this->index($request);
    }

    /**
     * Admin artwork detail.
     */
    public function artworkDetail(Request $request, string $username, Artwork $artwork)
    {
        if (!session('user_id') || session('role') !== 'admin') {
            return redirect()->route('login');
        }

        $admin        = User::findOrFail(session('user_id'));
        $expectedSlug = $admin->slug;

        if ($username !== $expectedSlug) {
            return redirect()->route('admin.artworks.show', [
                'username' => $expectedSlug,
                'artwork'  => $artwork->id,
            ]);
        }

        $categories = Category::orderBy('name')->get();

        return view('admin.artworkdetail', [
            'artwork'    => $artwork,
            'categories' => $categories,
        ]);
    }

    /**
     * Admin CRUD hub: /admin
     */
    public function crud(Request $request)
    {
        if (!session('user_id')) {
            return redirect()->route('login');
        }

        if ($request->expectsJson()) {
            return response()->json($this->adminCursorPayload($request));
        }

        $activeAdminTab = $request->input('admin_tab', 'admins');
        if (! in_array($activeAdminTab, ['admins', 'categories', 'reports', 'fees'], true)) {
            $activeAdminTab = 'admins';
        }
        $reportStatus = $this->normalizeReportStatus($request);
        $adminUserSearch = trim((string) $request->input('admin_user_q', ''));
        $adminCategorySearch = trim((string) $request->input('admin_category_q', ''));

        $artworkCount  = Artwork::count();
        $categoryCount = Category::count();
        $adminCount    = User::where('role', 'admin')->count();
        $userCount     = User::count();
        $suspendedCount = User::whereNotNull('suspended_at')->count();
        $openReportCount = ArtworkReport::where('status', 'open')->count();

        // Users that can be promoted to admin
        $eligibleUsers = User::where('role', '!=', 'admin')
            ->whereNull('suspended_at')
            ->orderBy('name')
            ->get();

        // Admins that can be demoted
        $demotableAdmins = User::where('role', 'admin')
            ->where('id', '!=', session('user_id'))
            ->orderBy('name')
            ->get();

        return view('admin.crud', [
            'artworkCount'    => $artworkCount,
            'categoryCount'   => $categoryCount,
            'adminCount'      => $adminCount,
            'userCount'       => $userCount,
            'suspendedCount'  => $suspendedCount,
            'openReportCount' => $openReportCount,
            'activeAdminTab'  => $activeAdminTab,
            'users'           => $this->adminUsersQuery($adminUserSearch)->cursorPaginate(12)->withQueryString(),
            'categories'      => $this->adminCategoriesQuery($adminCategorySearch)->cursorPaginate(12)->withQueryString(),
            'reports'         => $this->adminReportsQuery($reportStatus)->cursorPaginate(10)->withQueryString(),
            'reportStatus'    => $reportStatus,
            'adminUserSearch' => $adminUserSearch,
            'adminCategorySearch' => $adminCategorySearch,
            'eligibleUsers'   => $eligibleUsers,
            'demotableAdmins' => $demotableAdmins,
            'applicationFee' => AppSetting::integer('application_fee', AppSetting::DEFAULT_APPLICATION_FEE),
            'printboxRates' => AppSetting::printboxRates(),
        ]);
    }

    private function adminUsersQuery(?string $search = null)
    {
        return User::query()
            ->when($search, function ($query, string $search) {
                $like = '%' . strtolower($search) . '%';

                $query->where(function ($query) use ($like, $search) {
                    $query->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$like]);

                    if (ctype_digit($search)) {
                        $query->orWhere('id', (int) $search);
                    }
                });
            })
            ->orderBy('id');
    }

    private function adminCategoriesQuery(?string $search = null)
    {
        return Category::query()
            ->when($search, function ($query, string $search) {
                $like = '%' . strtolower($search) . '%';

                $query->where(function ($query) use ($like, $search) {
                    $query->whereRaw('LOWER(name) LIKE ?', [$like]);

                    if (ctype_digit($search)) {
                        $query->orWhere('id', (int) $search);
                    }
                });
            })
            ->orderBy('id');
    }

    private function adminReportsQuery(?string $status = null)
    {
        $query = ArtworkReport::with(['artwork.user', 'reporter']);

        if ($status === 'open') {
            $query->where('status', 'open');
        } elseif ($status === 'closed') {
            $query->where('status', '!=', 'open');
        }

        return $query
            ->select('artwork_reports.*')
            ->selectRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END as open_sort")
            ->orderBy('open_sort')
            ->orderByDesc('id');
    }

    private function normalizeReportStatus(Request $request): ?string
    {
        $status = $request->input('report_status');

        return in_array($status, ['open', 'closed'], true) ? $status : null;
    }

    private function adminCursorPayload(Request $request): array
    {
        $section = $request->input('admin_tab', 'admins');

        if ($section === 'categories') {
            $items = $this->adminCategoriesQuery(trim((string) $request->input('admin_category_q', '')))->cursorPaginate(12)->withQueryString();

            return $this->cursorPayload($items, 'admin.partials.category-row');
        }

        if ($section === 'reports') {
            $items = $this->adminReportsQuery($this->normalizeReportStatus($request))->cursorPaginate(10)->withQueryString();

            return $this->cursorPayload($items, 'admin.partials.report-row');
        }

        $items = $this->adminUsersQuery(trim((string) $request->input('admin_user_q', '')))->cursorPaginate(12)->withQueryString();

        return $this->cursorPayload($items, 'admin.partials.user-row', [
            'currentAdminId' => session('user_id'),
        ]);
    }

    private function cursorPayload($paginator, string $partial, array $extra = []): array
    {
        return [
            'data' => collect($paginator->items())
                ->map(fn ($item) => view($partial, array_merge(['item' => $item], $extra))->render())
                ->values()
                ->all(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'has_more' => $paginator->hasMorePages(),
            'total' => null,
        ];
    }

    public function updateFees(Request $request)
    {
        if (!session('user_id') || session('role') !== 'admin') {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'application_fee' => ['required', 'integer', 'min:0'],
            'printbox_bw_low_fee' => ['required', 'integer', 'min:0'],
            'printbox_bw_bulk_fee' => ['required', 'integer', 'min:0'],
            'printbox_color_fee' => ['required', 'integer', 'min:0'],
        ]);

        AppSetting::setInteger('application_fee', (int) $data['application_fee']);
        AppSetting::setInteger('printbox_bw_low_fee', (int) $data['printbox_bw_low_fee']);
        AppSetting::setInteger('printbox_bw_bulk_fee', (int) $data['printbox_bw_bulk_fee']);
        AppSetting::setInteger('printbox_color_fee', (int) $data['printbox_color_fee']);

        return redirect()
            ->route('admin.crud', ['admin_tab' => 'fees'])
            ->with('status', 'Fee settings updated.');
    }

    /**
     * Promote: admin.crud.promote
     */
    public function promoteAdmin(Request $request)
    {
        if (!session('user_id') || session('role') !== 'admin') {
            return redirect()->route('login');
        }

        $currentAdmin = User::findOrFail(session('user_id'));

        $data = $request->validate([
            'user_id'          => ['required', 'exists:users,id'],
            'current_password' => ['required', 'string'],
        ]);

        // Verify current admin password
        if (!Hash::check($data['current_password'], $currentAdmin->password)) {
            return back()->with('error', 'Incorrect password.');
        }

        $user = User::findOrFail($data['user_id']);

        if ($user->role === 'admin') {
            return back()->with('error', 'Selected user is already an admin.');
        }

        $user->role = 'admin';
        $user->save();

        return redirect()
            ->route('admin.crud', ['admin_tab' => 'admins'])
            ->with('success', 'User promoted to admin.');
    }

    /**
     * Demote: admin.crud.demote
     */
    public function demoteAdmin(Request $request)
    {
        if (!session('user_id') || session('role') !== 'admin') {
            return redirect()->route('login');
        }

        $currentAdmin = User::findOrFail(session('user_id'));

        $data = $request->validate([
            'user_id'          => ['required', 'exists:users,id'],
            'current_password' => ['required', 'string'],
        ]);

        // Verify current admin password
        if (!Hash::check($data['current_password'], $currentAdmin->password)) {
            return back()->with('error', 'Incorrect password.');
        }

        $user = User::findOrFail($data['user_id']);

        if ($user->role !== 'admin') {
            return back()->with('error', 'Selected user is not an admin.');
        }

        if ($user->id === $currentAdmin->id) {
            return back()->with('error', 'You cannot demote yourself here.');
        }

        $user->role = 'user';
        $user->save();

        return redirect()
            ->route('admin.crud', ['admin_tab' => 'admins'])
            ->with('success', 'Admin has been demoted to user.');
    }

    public function suspendUser(Request $request, User $user)
    {
        $admin = User::findOrFail(session('user_id'));

        if ($user->role === 'admin' || (int) $user->id === (int) $admin->id) {
            return back()->with('error', 'Admins cannot be suspended here.');
        }

        $user->update(['suspended_at' => now()]);

        return back()->with('status', 'User suspended.');
    }

    public function unsuspendUser(Request $request, User $user)
    {
        $user->update(['suspended_at' => null]);

        return back()->with('status', 'User restored.');
    }

    // ===== ARTWORK CRUD =====
    public function storeArtwork(Request $request, string $username)
    {
        if (!session('user_id') || session('role') !== 'admin') {
            return redirect()->route('login');
        }

        $admin = User::findOrFail(session('user_id'));

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'price'       => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'quantity'    => ['required', 'integer', 'min:0'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'image'       => ['nullable', 'string', 'max:255'],
        ]);

        Artwork::create($data);

        return redirect()
            ->route('admin.user', ['username' => $admin->slug])
            ->with('status', 'Artwork created.');
    }

    public function editArtwork(Request $request, string $username, Artwork $artwork)
    {
        return $this->artworkDetail($request, $username, $artwork);
    }

    public function updateArtwork(Request $request, string $username, Artwork $artwork)
    {
        if (!session('user_id') || session('role') !== 'admin') {
            return redirect()->route('login');
        }

        $admin = User::findOrFail(session('user_id'));

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'price'       => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'quantity'    => ['required', 'integer', 'min:0'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'image'       => ['nullable', 'string', 'max:255'],
        ]);

        $artwork->update($data);

        return redirect()
            ->route('admin.artworks.show', ['username' => $admin->slug, 'artwork' => $artwork->id])
            ->with('status', 'Artwork updated.');
    }

    public function resolveReport(Request $request, ArtworkReport $report)
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,resolved,rejected'],
        ]);

        $oldStatus = $report->status;
        $report->update(['status' => $data['status']]);
        $report->loadMissing(['artwork.user', 'reporter']);

        if ($data['status'] === 'resolved') {
            $report->artwork?->update([
                'visibility' => 'private',
                'share_token' => null,
                'published_at' => null,
                'archived_at' => now(),
            ]);
        }

        if ($oldStatus !== $data['status'] && in_array($data['status'], ['resolved', 'rejected'], true)) {
            $this->notifyReportDecision($report, $data['status']);
        }

        return back()->with('status', 'Report updated.');
    }

    private function notifyReportDecision(ArtworkReport $report, string $status): void
    {
        $artworkTitle = $report->artwork->name ?? 'an artwork';

        if ($report->reporter_id) {
            AppNotification::create([
                'user_id' => $report->reporter_id,
                'message' => $status === 'resolved'
                    ? 'Your report for "' . $artworkTitle . '" was approved.'
                    : 'Your report for "' . $artworkTitle . '" was rejected.',
            ]);
        }

        if ($status === 'resolved' && $report->artwork?->user_id) {
            AppNotification::create([
                'user_id' => $report->artwork->user_id,
                'message' => 'A report against your artwork "' . $artworkTitle . '" was approved.',
                'url' => route('account', ['tab' => 'images', 'visibility' => 'archived']),
            ]);
        }
    }

    public function destroyArtwork(string $username, Artwork $artwork)
    {
        if (!session('user_id') || session('role') !== 'admin') {
            return redirect()->route('login');
        }

        $admin = User::findOrFail(session('user_id'));

        $artwork->delete();

        return redirect()
            ->route('admin.user', ['username' => $admin->slug])
            ->with('status', 'Artwork deleted.');
    }

    // ===== CATEGORY CRUD =====
    public function storeCategory(Request $request)
    {
        if (!session('user_id') || session('role') !== 'admin') {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        Category::create($data);

        return redirect()
            ->route('admin.crud', ['admin_tab' => 'categories'])
            ->with('status', 'Category created.');
    }

    public function editCategory(Category $category)
    {
        return view('admin_edit_category', [
            'category' => $category,
        ]);
    }

    public function updateCategory(Request $request, Category $category)
    {
        if (!session('user_id') || session('role') !== 'admin') {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name,' . $category->id,
            ],
        ]);

        $category->update($data);

        return redirect()
            ->route('admin.crud', ['admin_tab' => 'categories'])
            ->with('status', 'Category updated.');
    }

    public function destroyCategory(Category $category)
    {
        if (!session('user_id') || session('role') !== 'admin') {
            return redirect()->route('login');
        }

        $category->delete();

        return redirect()
            ->route('admin.crud', ['admin_tab' => 'categories'])
            ->with('status', 'Category deleted.');
    }
}
