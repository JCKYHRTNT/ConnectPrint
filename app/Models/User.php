<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'profpic',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function cart() 
    {
        return $this->hasOne(Cart::class);
    }

    public function artworks()
    {
        return $this->hasMany(Product::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function notifications()
    {
        return $this->hasMany(AppNotification::class);
    }

    public function hasPurchased(Product $artwork): bool
    {
        return PurchaseItem::where('product_id', $artwork->id)
            ->whereHas('purchase', function ($query) {
                $query->where('user_id', $this->id)->where('status', 'completed');
            })
            ->exists();
    }

    public function getSlugAttribute(): string
    {
        return \Illuminate\Support\Str::slug($this->name);
    }

    public function getProfileImageUrlAttribute(): string
    {
        if ($this->profpic && file_exists(public_path($this->profpic))) {
            return asset($this->profpic);
        }

        return asset('images/default_avatar.jpg');
    }
}
