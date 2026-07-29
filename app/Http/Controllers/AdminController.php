<?php

namespace App\Http\Controllers;

use App\Models\Product as Artwork;
use App\Models\Category;
use App\Models\User;
use App\Models\ArtworkReport;
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
     * Admin CRUD hub: /a/{username}/admin
     */
    public function crud(Request $request, string $username)
    {
        if (!session('user_id')) {
            return redirect()->route('login');
        }

        $admin        = User::findOrFail(session('user_id'));
        $expectedSlug = $admin->slug;

        if ($username !== $expectedSlug) {
            return redirect()->route('admin.crud', [
                'username' => $expectedSlug,
            ] + $request->query());
        }

        $artworkCount  = Artwork::count();
        $categoryCount = Category::count();
        $adminCount    = User::where('role', 'admin')->count();

        // Users that can be promoted to admin
        $eligibleUsers = User::where('role', '!=', 'admin')->get();

        // Admins that can be demoted
        $demotableAdmins = User::where('role', 'admin')
            ->where('id', '!=', session('user_id'))
            ->get();

        return view('admin.crud', [
            'artworkCount'    => $artworkCount,
            'categoryCount'   => $categoryCount,
            'adminCount'      => $adminCount,
            'categories'      => Category::orderBy('id')->get(),
            'eligibleUsers'   => $eligibleUsers,
            'demotableAdmins' => $demotableAdmins,
            'applicationFee' => AppSetting::integer('application_fee', AppSetting::DEFAULT_APPLICATION_FEE),
            'printboxRates' => AppSetting::printboxRates(),
            'reports' => ArtworkReport::with(['artwork', 'reporter'])->where('status', 'open')->latest()->get(),
        ]);
    }

    public function updateFees(Request $request, string $username)
    {
        if (!session('user_id') || session('role') !== 'admin') {
            return redirect()->route('login');
        }

        $admin = User::findOrFail(session('user_id'));

        if ($username !== $admin->slug) {
            return redirect()->route('admin.crud', ['username' => $admin->slug]);
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
            ->route('admin.crud', ['username' => $admin->slug])
            ->with('status', 'Fee settings updated.');
    }

    /**
     * Promote: admin.crud.promote
     */
    public function promoteAdmin(Request $request, string $username)
    {
        if (!session('user_id') || session('role') !== 'admin') {
            return redirect()->route('login');
        }

        $currentAdmin = User::findOrFail(session('user_id'));
        $expectedSlug = $currentAdmin->slug;

        if ($username !== $expectedSlug) {
            return redirect()->route('admin.crud', [
                'username' => $expectedSlug,
            ] + $request->query());
        }

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
            ->route('admin.crud', ['username' => $currentAdmin->slug])
            ->with('success', 'User promoted to admin.');
    }

    /**
     * Demote: admin.crud.demote
     */
    public function demoteAdmin(Request $request, string $username)
    {
        if (!session('user_id') || session('role') !== 'admin') {
            return redirect()->route('login');
        }

        $currentAdmin = User::findOrFail(session('user_id'));
        $expectedSlug = $currentAdmin->slug;

        if ($username !== $expectedSlug) {
            return redirect()->route('admin.crud', [
                'username' => $expectedSlug,
            ] + $request->query());
        }

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
            ->route('admin.crud', ['username' => $currentAdmin->slug])
            ->with('success', 'Admin has been demoted to user.');
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

    public function resolveReport(Request $request, string $username, ArtworkReport $report)
    {
        $data = $request->validate([
            'status' => ['required', 'in:resolved,dismissed'],
            'archive_artwork' => ['nullable', 'boolean'],
        ]);

        $report->update(['status' => $data['status']]);

        if ($request->boolean('archive_artwork')) {
            $report->artwork?->update([
                'visibility' => 'private',
                'share_token' => null,
                'published_at' => null,
                'archived_at' => now(),
            ]);
        }

        return back()->with('status', 'Report updated.');
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
    public function storeCategory(Request $request, string $username)
    {
        if (!session('user_id') || session('role') !== 'admin') {
            return redirect()->route('login');
        }

        $admin = User::findOrFail(session('user_id'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        Category::create($data);

        return redirect()
            ->route('admin.crud', ['username' => $admin->slug])
            ->with('status', 'Category created.');
    }

    public function editCategory(string $username, Category $category)
    {
        return view('admin_edit_category', [
            'category' => $category,
        ]);
    }

    public function updateCategory(Request $request, string $username, Category $category)
    {
        if (!session('user_id') || session('role') !== 'admin') {
            return redirect()->route('login');
        }

        $admin = User::findOrFail(session('user_id'));

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
            ->route('admin.crud', ['username' => $admin->slug])
            ->with('status', 'Category updated.');
    }

    public function destroyCategory(string $username, Category $category)
    {
        if (!session('user_id') || session('role') !== 'admin') {
            return redirect()->route('login');
        }

        $admin = User::findOrFail(session('user_id'));

        $category->delete();

        return redirect()
            ->route('admin.crud', ['username' => $admin->slug])
            ->with('status', 'Category deleted.');
    }
}
