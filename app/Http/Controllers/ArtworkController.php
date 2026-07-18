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
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ArtworkController extends Controller
{
    public function index(Request $request)
    {
        $user = User::findOrFail(session('user_id'));
        $query = Artwork::with(['category', 'tags'])->where('user_id', $user->id)->latest();

        if ($request->filled('filter') && $request->filter !== 'all') {
            match ($request->filter) {
                'printable' => $query->where('is_printable', true),
                'display-only' => $query->where('is_printable', false),
                'draft' => $query->where('moderation_status', 'draft'),
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
            'artwork' => new Artwork(['visibility' => null, 'is_printable' => false]),
            'categories' => Category::orderBy('name')->get(),
            'suggestedTags' => $this->suggestedTagNames(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $user = User::findOrFail(session('user_id'));
        $data = $this->validatedData($request, true);
        $image = $request->file('image');
        $storedImage = $this->storeArtworkImage($image, $user);

        $artwork = Artwork::create([
            'user_id' => $user->id,
            'name' => $data['title'],
            'slug' => Str::slug($data['title']) . '-' . Str::random(6),
            'price' => $request->boolean('is_printable') ? (int) $data['creator_price'] : 0,
            'image' => null,
            'description' => $data['description'] ?? null,
            'quantity' => 1,
            'category_id' => $data['category_id'],
            'original_filename' => $storedImage['original_filename'],
            'original_path' => $storedImage['original_path'],
            'preview_path' => $storedImage['preview_path'],
            'mime_type' => $storedImage['mime_type'],
            'file_size' => $storedImage['file_size'],
            'width' => $storedImage['width'],
            'height' => $storedImage['height'],
            'visibility' => $data['visibility'],
            'share_token' => $data['visibility'] === 'unlisted' ? Str::random(40) : null,
            'is_printable' => $request->boolean('is_printable'),
            'moderation_status' => $data['visibility'] === 'private' ? 'approved' : 'pending',
            'published_at' => $data['visibility'] === 'private' ? null : now(),
        ]);

        $this->syncTags($artwork, $request->input('tags'));
        AppNotification::create(['user_id' => $user->id, 'message' => 'Your artwork "' . $artwork->name . '" was uploaded.']);

        return redirect()->route('artworks.index')->with('success', 'Artwork uploaded.');
    }

    public function edit(Artwork $artwork)
    {
        $this->authorizeOwner($artwork);

        return view('artworks.form', [
            'artwork' => $artwork->load('tags'),
            'categories' => Category::orderBy('name')->get(),
            'suggestedTags' => $this->suggestedTagNames(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Artwork $artwork)
    {
        $this->authorizeOwner($artwork);
        $data = $this->validatedData($request, false);
        $visibilityChanged = $artwork->visibility !== $data['visibility'];

        $updates = [
            'name' => $data['title'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'],
            'visibility' => $data['visibility'],
            'share_token' => $data['visibility'] === 'unlisted' ? ($artwork->share_token ?: Str::random(40)) : null,
            'is_printable' => $request->boolean('is_printable'),
            'price' => $request->boolean('is_printable') ? (int) $data['creator_price'] : 0,
            'moderation_status' => $data['visibility'] === 'private'
                ? 'approved'
                : ($visibilityChanged ? 'pending' : $artwork->moderation_status),
            'published_at' => $data['visibility'] !== 'private' ? ($artwork->published_at ?: now()) : null,
        ];

        if ($request->hasFile('image')) {
            $updates = array_merge($updates, $this->storeArtworkImage($request->file('image'), User::findOrFail($artwork->user_id)));
            $updates['image'] = null;
        }

        $artwork->update($updates);

        $this->syncTags($artwork, $request->input('tags'));

        return redirect()->route('artworks.index')->with('success', 'Artwork updated.');
    }

    public function saveDraft(Request $request, ?Artwork $artwork = null)
    {
        $user = User::findOrFail(session('user_id'));

        if ($artwork) {
            $this->authorizeOwner($artwork);
        }

        $data = $request->validate([
            'draft_artwork_id' => ['nullable', 'integer', 'exists:products,id'],
            'title' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['nullable', 'in:public,unlisted,private'],
            'is_printable' => ['nullable', 'boolean'],
            'creator_price' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! $artwork && ! empty($data['draft_artwork_id'])) {
            $artwork = Artwork::where('id', $data['draft_artwork_id'])
                ->where('user_id', $user->id)
                ->first();
        }

        $title = trim((string) ($data['title'] ?? ''));
        $hasPrintablePrice = $request->boolean('is_printable') && (int) ($data['creator_price'] ?? 0) > 0;

        $updates = [
            'user_id' => $user->id,
            'name' => $title !== '' ? $title : 'Untitled draft',
            'slug' => Str::slug($title !== '' ? $title : 'untitled-draft') . '-' . Str::random(6),
            'price' => $hasPrintablePrice ? (int) $data['creator_price'] : 0,
            'image' => null,
            'description' => $data['description'] ?? null,
            'quantity' => 1,
            'category_id' => $data['category_id'] ?? null,
            'visibility' => 'private',
            'share_token' => null,
            'is_printable' => $request->boolean('is_printable'),
            'moderation_status' => 'draft',
            'published_at' => null,
        ];

        if ($request->hasFile('image')) {
            $updates = array_merge($updates, $this->storeArtworkImage($request->file('image'), $user));
        }

        if ($artwork) {
            unset($updates['user_id']);

            if (! $request->hasFile('image')) {
                unset(
                    $updates['original_filename'],
                    $updates['original_path'],
                    $updates['preview_path'],
                    $updates['mime_type'],
                    $updates['file_size'],
                    $updates['width'],
                    $updates['height']
                );
            }

            $artwork->update($updates);
        } else {
            $artwork = Artwork::create($updates);
        }

        $this->syncTags($artwork, $request->input('tags'));

        return response()->json([
            'id' => $artwork->id,
            'edit_url' => route('artworks.edit', ['artwork' => $artwork->id]),
            'draft_url' => route('artworks.draft.update', ['artwork' => $artwork->id]),
        ]);
    }

    public function archive(Artwork $artwork)
    {
        $this->authorizeOwner($artwork);
        $artwork->update(['visibility' => 'archived', 'archived_at' => now()]);
        return back()->with('success', 'Artwork archived.');
    }

    public function restore(Artwork $artwork)
    {
        $this->authorizeOwner($artwork);
        $artwork->update(['visibility' => 'private', 'archived_at' => null]);
        return back()->with('success', 'Artwork restored as private.');
    }

    public function destroy(Artwork $artwork)
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

    public function preview(Artwork $artwork)
    {
        $viewer = session('user_id') ? User::find(session('user_id')) : null;
        $isOwner = $viewer && (int) $viewer->id === (int) $artwork->user_id;
        $isAdmin = $viewer && $viewer->role === 'admin';
        $isPublic = $artwork->visibility === 'public'
            && $artwork->moderation_status === 'approved'
            && ! $artwork->isArchived();
        $isUnlisted = $artwork->visibility === 'unlisted'
            && ! $artwork->isArchived();

        abort_unless($isOwner || $isAdmin || $isPublic || $isUnlisted, 403);

        if ($artwork->preview_path) {
            if (Storage::disk('public')->exists($artwork->preview_path)) {
                return Storage::disk('public')->response($artwork->preview_path);
            }

            $legacyPreviewPath = 'public/' . $artwork->preview_path;
            if (Storage::exists($legacyPreviewPath)) {
                return Storage::response($legacyPreviewPath);
            }
        }

        if ($artwork->image && file_exists(public_path($artwork->image))) {
            return response()->file(public_path($artwork->image));
        }

        abort(404);
    }

    public function printFile(Artwork $artwork)
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
            'tags' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', 'in:public,unlisted,private'],
            'is_printable' => ['nullable', 'boolean'],
            'creator_price' => [Rule::requiredIf($request->boolean('is_printable')), 'nullable', 'integer', 'min:1'],
        ], [
            'title.required' => '*Title is required',
            'image.required' => '*Upload Image is required',
            'image.image' => 'Upload Image must be a valid image file.',
            'image.mimes' => 'Upload Image must be a JPG, JPEG, PNG, or WEBP file.',
            'image.max' => 'Upload Image must be 10 MB or smaller.',
            'category_id.required' => '*Category is required',
            'category_id.exists' => 'Choose a valid category.',
            'visibility.required' => '*Visibility is required',
            'visibility.in' => 'Choose a valid visibility.',
            'creator_price.required' => '*Price is required when printable access is enabled',
            'creator_price.integer' => 'Price must be a whole number.',
            'creator_price.min' => 'Price must be greater than 0.',
        ]);
    }

    private function storeArtworkImage($image, User $user): array
    {
        [$width, $height] = getimagesize($image->getRealPath()) ?: [null, null];
        $uuid = (string) Str::uuid();
        $extension = $image->getClientOriginalExtension();
        $originalPath = $image->storeAs("artworks/{$user->id}/{$uuid}", "original.{$extension}");
        $previewPath = $image->storeAs("artworks/{$user->id}/{$uuid}", "preview.{$extension}", 'public');

        return [
            'original_filename' => $image->getClientOriginalName(),
            'original_path' => $originalPath,
            'preview_path' => $previewPath,
            'mime_type' => $image->getMimeType(),
            'file_size' => $image->getSize(),
            'width' => $width,
            'height' => $height,
        ];
    }

    private function syncTags(Artwork $artwork, ?string $rawTags): void
    {
        $tagIds = collect(explode(',', (string) $rawTags))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->map(fn ($tag) => Str::title($tag))
            ->unique()
            ->map(function ($tag) {
                return Tag::firstOrCreate(['slug' => Str::slug($tag)], ['name' => $tag])->id;
            })
            ->all();

        $artwork->tags()->sync($tagIds);
    }

    private function suggestedTagNames(): array
    {
        $fallback = collect(['digitalart', 'fanart', 'magic', 'digitalpainting', 'wallpaper', 'animedrawing', 'adoptable']);

        return Tag::whereHas('artworks', fn ($query) => $query->whereIn('visibility', ['public', 'unlisted']))
            ->orderBy('name')
            ->pluck('name')
            ->merge($fallback)
            ->map(fn ($tag) => Str::of($tag)->replace(' ', '')->lower()->toString())
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }

    private function authorizeOwner(Artwork $artwork): void
    {
        abort_unless((int) $artwork->user_id === (int) session('user_id'), 403);
    }
}
