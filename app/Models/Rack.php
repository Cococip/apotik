<?php

namespace App\Models;

use App\Core\Model;

class Rack extends Model
{
    protected static string $table = 'racks';
    protected static bool $softDeletes = true;
    protected static array $fillable = ['code', 'name', 'description'];
}
