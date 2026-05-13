<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NinjaBranch extends Model
{
    use HasFactory;
    protected $fillable = [
        'pos_key',
        'branch_name',
        'brand_id',
        'pos_system',
        'pos_restaurant_id',
        'aggregator_menu_id'
	];
}
