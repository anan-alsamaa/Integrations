<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOptionsValue extends Model
{
    use HasFactory;
    protected $fillable = [
        'option_key', 'option_id', 'option_name', 'option_value_key',
        'option_value_id', 'option_value_name', 'option_value_price', 'quantity'
    ];

    public function productOption()
    {
        return $this->belongsTo(ProductOption::class);
    }
}
