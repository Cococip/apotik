<?php

namespace App\Models;

use App\Core\Model;

class Category extends Model
{
    protected static string $table = 'categories';
    protected static bool $softDeletes = true;
    protected static array $fillable = ['name', 'slug', 'description', 'status'];
}
