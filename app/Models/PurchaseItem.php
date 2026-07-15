<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'creator_id',
        'artwork_title_snapshot',
        'creator_name_snapshot',
        'creator_price',
        'original_path_snapshot',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function artwork()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
