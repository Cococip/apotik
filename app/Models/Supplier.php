<?php

namespace App\Models;

use App\Core\Model;

class Supplier extends Model
{
    protected static string $table = 'suppliers';
    protected static bool $softDeletes = true;
    protected static array $fillable = ['code', 'name', 'contact_person', 'phone', 'email', 'address', 'npwp', 'payment_term_days', 'status'];
}
