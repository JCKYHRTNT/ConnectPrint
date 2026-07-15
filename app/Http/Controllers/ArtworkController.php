<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Category;
use App\Models\Product as Artwork;
use App\Models\PurchaseItem;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArtworkController extends Controller
{
    public function index(string $username, Request $request)
    {
        $user = User::findOrFail(session('user_id'));
        $query = Artwork::with(['category', 'tags'])->where('user_id', $user->id)->latest();

        if ($request->filled('filter') && $request->filter !== 'all') {
            match ($request->filter) {
                'printable' => $query->where('is_printable', true),
                'display-only' => $query->where('is_printable', false),
                default => $query->where('visibility', $request->filter),
            };
        }

        return view('artworks.index', [
            'artworks' => $query->paginate(12)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('artworks.form', [
            'artwork' => new Artwork(['visibility' => 'private', 'is_printable' => true]),
            'categories' => Category::orderBy('name')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $user = User::findOrFail(session('user_id'));
        $data = $this->validatedData($request, true);
        $image = $request->file('image');
        [$width, $height] = getimagesize($image->getRealPath()) ?: [null, null];
        $uuid = (string) Str::uuid();
        $extension = $image->getClientOriginalExtension();
        $originalPath = $image->storeAs("artworks/{$user->id}/{$uuid}", "original.{$extension}");
        $previewPath = $image->storeAs("public/artworks/{$user->id}/{$uuid}", "preview.{$extension}");

        $artwork = Artwork::create([
            'user_id' => $user->id,
            'name' => $data['title'],
            'slug' => Str::slug($data['title']) . '-' . Str::random(6),
            'price' => $request->boolean('is_printable') ? (int) $data['creator_price'] : 0,
            'image' => null,
            'description' => $data['description'] ?? null,
            'quantity' => 1,
            'category_id' => $data['category_id'],
            'original_filename' => $image->getClientOriginalName(),
            'original_path' => $originalPath,
            'preview_path' => Str::after($previewPath, 'public/'),
            'mime_type' => $image->getMimeType(),
            'file_size' => $image->getSize(),
            'width' => $width,
            'height' => $height,
            'visibility' => $data['visibility'],
            'share_token' => $data['visibility'] === 'unlisted' ? Str::random(40) : null,
            'is_printable' => $request->boolean('is_printable'),
            'moderation_status' => $data['visibility'] === 'private' ? 'approved' : 'pending',
            'published_at' => $data['visibility'] === 'private' ? null : now(),
        ]);

        $this->syncTags($artwork, $request->input('tags'));
        AppNotification::create(['user_id' => $user->id, 'message' => 'Your artwork "' . $artwork->name . '" was uploaded.']);

        return redirect()->route('artworks.index', ['username' => $user->slug])->with('success', 'Artwork uploaded.');
    }

    public function edit(string $username, Artwork $artwork)
    {
        $this->authorizeOwner($artwork);

        return view('artworks.form', [
            'artwork' => $artwork->load('tags'),
            'categories' => Category::orderBy('name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, string $username, Artwork $artwork)
    {
        $this->authorizeOwner($artwork);
        $data = $this->validatedData($request, false);
        $visibilityChanged = $artwork->visibility !== $data['visibility'];

        $artwork->update([
            'name' => $data['title'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'],
            'visibility' => $data['visibility'],
            'share_token' => $data['visibility'] === 'unlisted' ? ($artwork->share_token ?: Str::random(40)) : null,
            'is_printable' => $request->boolean('is_printable'),
            'price' => $request->boolean('is_printable') ? (int) $data['creator_price'] : 0,
            'moderation_status' => $visibilityChanged && $data['visibility'] !== 'private' ? 'pending' : $artwork->moderation_status,
            'published_at' => $data['visibility'] !== 'private' ? ($artwork->published_at ?: now()) : null,
        ]);

        $this->syncTags($artwork, $request->input('tags'));

        return redirect()->route('artworks.index', ['username' => User::findOrFail(session('user_id'))->slug])->with('success', 'Artwork updated.');
    }

    public function archive(string $username, Artwork $artwork)
    {
        $this->authorizeOwner($artwork);
        $artwork->update(['visibility' => 'archived', 'archived_at' => now()]);
        return back()->with('success', 'Artwork archived.');
    }

    public function restore(string $username, Artwork $artwork)
    {
        $this->authorizeOwner($artwork);
        $artwork->update(['visibility' => 'private', 'archived_at' => null]);
        return back()->with('success', 'Artwork restored as private.');
    }

    public function destroy(string $username, Artwork $artwork)
    {
        $this->authorizeOwner($artwork);
        if ($artwork->purchaseItems()->exists()) {
            $artwork->update(['visibility' => 'archived', 'archived_at' => now()]);
            return back()->with('error', 'Artwork has purchases, so it was archived instead of deleted.');
        }
        $artwork->delete();
        return back()->with('success', 'Artwork deleted.');
    }

    public function shared(string $shareToken)
    {
        $artwork = Artwork::with(['category', 'user', 'tags'])->where('share_token', $shareToken)->firstOrFail();
        if ($artwork->visibility !== 'unlisted' || $artwork->isArchived()) {
            abort(404);
        }
        return view('artworkdetail', [
            'artwork' => $artwork,
            'viewer' => session('user_id') ? User::find(session('user_id')) : null,
            'canPurchase' => $artwork->canBePurchasedBy(session('user_id') ? User::find(session('user_id')) : null),
        ]);
    }

    public function printFile(string $username, Artwork $artwork)
    {
        $user = User::findOrFail(session('user_id'));
        $allowed = $user->role === 'admin'
            || (int) $artwork->user_id === (int) $user->id
            || $user->hasPurchased($artwork);

        abort_unless($allowed, 403);

        if ($artwork->original_path && Storage::exists($artwork->original_path)) {
            return Storage::download($artwork->original_path, $artwork->original_filename ?: $artwork->name);
        }

        if ($artwork->image && file_exists(public_path($artwork->image))) {
            return response()->download(public_path($artwork->image), basename($artwork->image));
        }

        abort(404);
    }

    private function validatedData(Request $request, bool $requireImage): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => [$requireImage ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'category_id' => ['required', 'exists:categories,id'],
            'tags' => ['nullable', 'string', 'max:255'],
            'visibility' => ['required', 'in:public,unlisted,private'],
            'is_printable' => ['nullable', 'boolean'],
            'creator_price' => ['required_if:is_printable,1', 'integer', 'min:0'],
        ]);
    }

    private function syncTags(Artwork $artwork, ?string $rawTags): void
    {
        $tagIds = collect(explode(',', (string) $rawTags))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->map(fn ($tag) => Str::title($tag))
            ->unique()
            ->take(5)
            ->map(function ($tag) {
                return Tag::firstOrCreate(['slug' => Str::slug($tag)], ['name' => $tag])->id;
            })
            ->all();

        $artwork->tags()->sync($tagIds);
    }

    private function authorizeOwner(Artwork $artwork): void
    {
        abort_unless((int) $artwork->user_id === (int) session('user_id'), 403);
    }
}
