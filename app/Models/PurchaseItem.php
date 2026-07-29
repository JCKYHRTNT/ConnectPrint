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
        'printbox_fee',
        'printbox_requested',
        'printbox_mode',
        'printbox_sheet_count',
        'original_path_snapshot',
    ];

    protected $casts = [
        'printbox_requested' => 'boolean',
        'printbox_sheet_count' => 'integer',
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
