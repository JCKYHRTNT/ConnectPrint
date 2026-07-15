<?php

namespace App\Http\Controllers;

use App\Models\Product as Artwork;
use App\Models\Category;
use App\Models\User;
use App\Models\ArtworkReport;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Admin dashboard (artwork list).
     */
    public function index(Request $request)
    {
        $categoryId = $request->filled('category')
            ? (int) $request->input('category')
            : null;

        $query = $request->input('q');

        // Categories
        $categories    = Category::orderBy('name')->get();
        $categoryNames = $categories->pluck('name', 'id')->toArray();

        // Base artwork query
        $artworksQuery = Artwork::with(['category', 'user']);

        if (!is_null($categoryId)) {
            $artworksQuery->where('category_id', $categoryId);
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
            'pendingArtworks' => Artwork::with('user')->where('moderation_status', 'pending')->latest()->get(),
            'reports' => ArtworkReport::with(['artwork', 'reporter'])->where('status', 'open')->latest()->get(),
        ]);
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

    public function moderateArtwork(Request $request, string $username, Artwork $artwork)
    {
        $data = $request->validate([
            'moderation_status' => ['required', 'in:approved,rejected'],
            'moderation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $artwork->update($data);

        if ($artwork->user_id) {
            AppNotification::create([
                'user_id' => $artwork->user_id,
                'message' => 'Your artwork "' . $artwork->name . '" was ' . $data['moderation_status'] . '.',
            ]);
        }

        return back()->with('status', 'Artwork moderation updated.');
    }

    public function resolveReport(Request $request, string $username, ArtworkReport $report)
    {
        $data = $request->validate([
            'status' => ['required', 'in:resolved,dismissed'],
            'archive_artwork' => ['nullable', 'boolean'],
        ]);

        $report->update(['status' => $data['status']]);

        if ($request->boolean('archive_artwork')) {
            $report->artwork?->update(['visibility' => 'archived', 'archived_at' => now()]);
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
