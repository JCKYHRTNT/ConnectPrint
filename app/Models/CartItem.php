<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'printbox_requested',
        'printbox_mode',
        'printbox_sheet_count',
    ];

    protected $casts = [
        'printbox_requested' => 'boolean',
        'printbox_sheet_count' => 'integer',
    ];

    public function cart() 
    {
        return $this->belongsTo(Cart::class);
    }

    public function artwork() 
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
