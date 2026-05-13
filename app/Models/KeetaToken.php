<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeetaToken extends Model
{
    use HasFactory;

    protected $table = 'keetatoken';

    // Specify which attributes are mass assignable
    protected $fillable = [
        'brandId',
        'accessToken',
        'tokenType',
        'expiresIn',
        'refreshToken',
        'scope',
        'issuedAtTime',
    ];
}
