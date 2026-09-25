<?php

namespace App\Models;

use App\Core\Model;

class CashShift extends Model
{
    protected static string $table = 'cash_shifts';
    protected static array $fillable = [
        'shift_number', 'cashier_id', 'opening_balance', 'opening_at',
        'closing_balance_system', 'closing_balance_actual', 'difference', 'closing_at', 'status', 'notes',
    ];
}
