<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use App\Models\ArtworkReport;
use App\Models\Product as Artwork;
use App\Models\Purchase;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArtworkSeeder extends Seeder
{
    public function run(): void
    {
        $tags = collect(['Urban', 'Nature', 'Poster', 'Minimal', 'Colorful', 'Black White', 'Landscape', 'Abstract', 'Print Ready', 'Local'])
            ->mapWithKeys(fn ($name) => [$name => Tag::create(['name' => $name, 'slug' => Str::slug($name)])]);

        $rows = [
            ['City Lights', 2, 1, 25000, 'public', true, 'approved', ['Urban', 'Print Ready']],
            ['Rainy Window', 2, 1, 15000, 'public', true, 'approved', ['Minimal', 'Black White']],
            ['Botanical Calm', 3, 6, 0, 'public', false, 'approved', ['Nature']],
            ['Jakarta Poster', 3, 4, 30000, 'public', true, 'pending', ['Poster', 'Local']],
            ['Private Draft', 2, 3, 0, 'private', false, 'approved', ['Abstract']],
            ['Hidden Geometry', 3, 5, 20000, 'unlisted', true, 'approved', ['Abstract', 'Print Ready']],
            ['Archived Sunset', 2, 1, 18000, 'private', true, 'approved', ['Landscape'], true],
            ['Forest Morning', 4, 6, 22000, 'public', true, 'approved', ['Nature', 'Landscape']],
            ['Blue Study', 4, 3, 0, 'public', false, 'approved', ['Abstract']],
            ['Festival Lines', 2, 2, 27000, 'public', true, 'rejected', ['Colorful']],
            ['Quiet Alley', 3, 1, 19000, 'public', true, 'approved', ['Urban']],
            ['Shape System', 4, 5, 21000, 'unlisted', true, 'pending', ['Minimal', 'Abstract']],
        ];

        $artworks = collect($rows)->map(function ($row) use ($tags) {
            [$name, $userId, $categoryId, $price, $visibility, $printable, $status, $tagNames] = array_pad($row, 9, false);
            $isArchived = (bool) ($row[8] ?? false);
            $artwork = Artwork::create([
                'user_id' => $userId,
                'name' => $name,
                'slug' => Str::slug($name),
                'price' => $printable ? $price : 0,
                'image' => 'images/placeholders/blank-artwork.svg',
                'description' => null,
                'quantity' => 1,
                'category_id' => $categoryId,
                'original_filename' => 'blank-artwork.svg',
                'original_path' => null,
                'preview_path' => 'images/placeholders/blank-artwork.svg',
                'mime_type' => 'image/svg+xml',
                'file_size' => 190,
                'width' => 1200,
                'height' => 900,
                'visibility' => $visibility,
                'share_token' => $visibility === 'unlisted' ? Str::random(40) : null,
                'is_printable' => $printable,
                'moderation_status' => $status,
                'moderation_reason' => $status === 'rejected' ? 'Seeded rejection example.' : null,
                'published_at' => ! $isArchived && in_array($visibility, ['public', 'unlisted'], true) ? now() : null,
                'archived_at' => $isArchived ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $artwork->tags()->sync(collect($tagNames)->map(fn ($name) => $tags[$name]->id));
            return $artwork;
        });

        $purchase = Purchase::create([
            'user_id' => 3,
            'purchase_number' => 'PBX-' . now()->format('Ymd') . '-0001',
            'status' => 'completed',
            'payment_status' => 'simulated_paid',
            'subtotal' => 40000,
            'total' => 40000,
        ]);

        foreach ($artworks->whereIn('name', ['City Lights', 'Rainy Window']) as $artwork) {
            $purchase->items()->create([
                'product_id' => $artwork->id,
                'creator_id' => $artwork->user_id,
                'artwork_title_snapshot' => $artwork->name,
                'creator_name_snapshot' => $artwork->creatorName(),
                'creator_price' => $artwork->price,
                'original_path_snapshot' => $artwork->original_path,
            ]);
        }

        ArtworkReport::create(['product_id' => $artworks[0]->id, 'reporter_id' => 3, 'reason' => 'copyright', 'details' => 'Seeded report example.', 'status' => 'open']);
        ArtworkReport::create(['product_id' => $artworks[7]->id, 'reporter_id' => 2, 'reason' => 'other', 'details' => 'Second seeded report example.', 'status' => 'open']);

        AppNotification::create(['user_id' => 2, 'message' => 'Bobby purchased printable access to "City Lights".']);
        AppNotification::create(['user_id' => 3, 'message' => 'Your purchase ' . $purchase->purchase_number . ' is complete.']);
    }
}
