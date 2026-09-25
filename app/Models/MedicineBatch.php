<?php

namespace App\Models;

use App\Core\Model;

class MedicineBatch extends Model
{
    protected static string $table = 'medicine_batches';
    protected static array $fillable = [
        'medicine_id', 'batch_number', 'received_date', 'production_date', 'expired_date',
        'purchase_price', 'selling_price', 'initial_qty', 'status',
    ];
}
