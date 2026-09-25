<?php

namespace App\Models;

use App\Core\Model;

class Manufacturer extends Model
{
    protected static string $table = 'manufacturers';
    protected static bool $softDeletes = true;
    protected static array $fillable = ['name', 'address', 'phone'];
}
