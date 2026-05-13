<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrandAggregatorConfiguration extends Model
{
    use HasFactory;
    protected $table = 'brand_aggregator_configuration';

    protected $fillable = [
        'pos_system',
        'brand_id',
        'aggregator_name',
        'aggregator id',
        'order_mode_id',
        'payment_method',
        'tender_name',
        'tender_id',
        'tax_id',
    ];
}