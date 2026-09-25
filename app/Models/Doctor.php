<?php

namespace App\Models;

use App\Core\Model;

class Doctor extends Model
{
    protected static string $table = 'doctors';
    protected static bool $softDeletes = true;
    protected static array $fillable = ['code', 'name', 'sip_number', 'specialization', 'phone', 'address', 'status'];
}
