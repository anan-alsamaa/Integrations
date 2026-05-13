<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeetaBranch extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'keeta_branches';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'branch_name',
        'pos_key',
        'pos_system',
        'keeta_id',
        'brand_id',
        'brand_reference_id',
        'pos_restaurant_id',
        'aggregator_menu_id',
    ];
}
