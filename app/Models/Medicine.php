<?php

namespace App\Models;

use App\Core\Model;

class Medicine extends Model
{
    protected static string $table = 'medicines';
    protected static bool $softDeletes = true;
    protected static array $fillable = [
        'code', 'barcode', 'name', 'generic_name', 'brand_name',
        'category_id', 'medicine_type_id', 'medicine_group_id', 'manufacturer_id',
        'unit_id', 'large_unit_id', 'content_per_unit',
        'purchase_price', 'selling_price', 'selling_price_prescription',
        'minimum_stock', 'maximum_stock', 'rack_id', 'description', 'status',
    ];
}
