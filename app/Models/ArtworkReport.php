<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtworkReport extends Model
{
    protected $fillable = ['product_id', 'reporter_id', 'reason', 'details', 'status'];

    public function artwork()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
