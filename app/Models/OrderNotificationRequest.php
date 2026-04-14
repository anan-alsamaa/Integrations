<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderNotificationRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id', 'status', 'status_change_date_time', 'pos_id', 'pos_key'
    ];

    protected $casts = [
        'status_change_date_time' => 'datetime',
    ];
}
