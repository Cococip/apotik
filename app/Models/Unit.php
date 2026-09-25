<?php

namespace App\Models;

use App\Core\Model;

class Unit extends Model
{
    protected static string $table = 'units';
    protected static bool $softDeletes = true;
    protected static array $fillable = ['name', 'symbol'];
}
