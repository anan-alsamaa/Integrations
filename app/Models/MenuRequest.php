<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_options', 'products', 'schedules'
    ];

    protected $casts = [
        'product_options' => 'array',
        'products' => 'array',
        'schedules' => 'array',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function productOptions()
    {
        return $this->hasMany(ProductOption::class);
    }
}
