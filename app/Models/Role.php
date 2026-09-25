<?php

namespace App\Models;

use App\Core\Model;

class Role extends Model
{
    protected static string $table = 'roles';
    protected static array $fillable = ['name', 'slug', 'description', 'is_system'];
}
