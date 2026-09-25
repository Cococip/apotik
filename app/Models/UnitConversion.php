<?php

namespace App\Models;

use App\Core\Model;

class UnitConversion extends Model
{
    protected static string $table = 'unit_conversions';
    protected static array $fillable = ['medicine_id', 'from_unit_id', 'to_unit_id', 'conversion_factor'];
}
