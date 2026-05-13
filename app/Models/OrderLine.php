<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderLine extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'note', 'price', 'product_id', 'product_key',
        'product_options_values', 'quantity','ketaa_order_id'
    ];

    protected $casts = [
        'product_options_values' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_key', 'product_key');
    }

    public function productOptionsValues()
    {
        return $this->hasMany(ProductOptionsValue::class);
    }
}
