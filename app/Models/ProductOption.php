<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOption extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_option_key', 'name_en', 'name_ar', 'title_en', 'title_ar',
        'min', 'max', 'enable_quantity', 'option_values'
    ];

    protected $casts = [
        'option_values' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues()
    {
        return $this->hasMany(ProductOptionsValue::class);
    }
}
