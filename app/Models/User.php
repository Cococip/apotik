<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected static bool $softDeletes = true;
    protected static array $fillable = ['username', 'email', 'password', 'full_name', 'phone', 'role_id', 'status'];
}
