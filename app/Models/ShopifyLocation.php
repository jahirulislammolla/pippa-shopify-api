<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyLocation extends Model
{
    protected $fillable = [
        'shop_domain',
        'shopify_location_id',
        'name',
        'address1',
        'city',
        'province_code',
        'country_code',
        'zip',
    ];
}
