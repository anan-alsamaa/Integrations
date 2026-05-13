<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AggregatorsConfiguration extends Model
{
    use HasFactory;
    protected $table = 'aggregators_configrations';

    protected $fillable = [
        'postKey',
        'PAY_STORE_TENDERID_Cash',
        'PAY_STORE_TENDERID_Credit',
        'PAY_SUB_TYPE_Cash',
        'PAY_SUB_TYPE_Credit',
        'PAY_STATUS',
        'ConceptID',
        'LicenseCode',
        'MenuTemplateID',
        'AddressID',
        'CustomerID',
        'OrderMode',
        'StoreID',
    ];
}
