<?php

namespace App\Models;

use App\Core\Model;

class MedicineType extends Model
{
    protected static string $table = 'medicine_types';
    protected static bool $softDeletes = true;
    protected static array $fillable = ['name', 'description', 'status'];
}
