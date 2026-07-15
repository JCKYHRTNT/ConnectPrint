<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'price',
        'image',
        'description',
        'quantity',
        'category_id',
        'original_filename',
        'original_path',
        'preview_path',
        'mime_type',
        'file_size',
        'width',
        'height',
        'visibility',
        'share_token',
        'is_printable',
        'moderation_status',
        'moderation_reason',
        'published_at',
        'archived_at',
    ];

    protected $casts = [
        'is_printable' => 'boolean',
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function category() 
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cartItems() 
    {
        return $this->hasMany(CartItem::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function reports()
    {
        return $this->hasMany(ArtworkReport::class);
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->preview_path) {
            if (str_starts_with($this->preview_path, 'images/')) {
                return asset($this->preview_path);
            }

            return asset('storage/' . $this->preview_path);
        }

        if ($this->image && file_exists(public_path($this->image))) {
            return asset($this->image);
        }

        return asset('images/placeholders/blank-artwork.svg');
    }

    public function getCreatorPriceAttribute(): int
    {
        return (int) $this->price;
    }

    public function creatorName(): string
    {
        return $this->user->name ?? 'ConnectPrint Creator';
    }

    public function isArchived(): bool
    {
        return $this->visibility === 'archived' || $this->archived_at !== null;
    }

    public function isApprovedPublic(): bool
    {
        return $this->visibility === 'public'
            && $this->moderation_status === 'approved'
            && ! $this->isArchived();
    }

    public function canBePurchasedBy(?User $user): bool
    {
        if (! $user || ! $this->is_printable || $this->isArchived()) {
            return false;
        }

        if ((int) $this->user_id === (int) $user->id) {
            return false;
        }

        if (! in_array($this->visibility, ['public', 'unlisted'], true)) {
            return false;
        }

        if ($this->moderation_status !== 'approved') {
            return false;
        }

        return ! $user->hasPurchased($this);
    }
}
