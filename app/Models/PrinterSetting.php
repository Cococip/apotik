<?php

namespace App\Models;

use App\Core\Model;

class PrinterSetting extends Model
{
    protected static string $table = 'printer_settings';
    protected static array $fillable = ['name', 'paper_size', 'is_default', 'auto_print', 'header_text', 'footer_text'];
}
