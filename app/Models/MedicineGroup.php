<?php

namespace App\Models;

use App\Core\Model;

class MedicineGroup extends Model
{
    protected static string $table = 'medicine_groups';
    protected static bool $softDeletes = true;
    protected static array $fillable = ['name', 'code', 'requires_prescription', 'description', 'status'];
}
