<?php

namespace App\Models;

use App\Core\Model;

class Pharmacy extends Model
{
    protected static string $table = 'pharmacies';
    protected static array $fillable = ['name', 'logo', 'address', 'phone', 'email', 'npwp', 'receipt_footer'];
}
