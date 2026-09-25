<?php

namespace App\Models;

use App\Core\Model;

class Patient extends Model
{
    protected static string $table = 'customers';
    protected static bool $softDeletes = true;
    protected static array $fillable = ['patient_number', 'name', 'birth_date', 'gender', 'address', 'phone', 'email', 'notes'];
}
