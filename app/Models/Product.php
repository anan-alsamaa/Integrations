<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_key', 'name_en', 'name_ar', 'description_en', 'description_ar', 'product_options', 'tags', 'groups', 'media_files', 'schedule'
    ];

    protected $casts = [
        'product_options' => 'array',
        'tags' => 'array',
        'groups' => 'array',
        'media_files' => 'array',
    ];

    public function productOptions()
    {
        return $this->hasMany(ProductOption::class);
    }
}
