<?php

namespace App\Services;

use App\Core\Database;

/**
 * Generates internal barcodes in the APO###### format (§20/§9 — only on
 * explicit user confirmation, never silently attached to a medicine).
 */
class BarcodeCodeGenerator
{
    public static function next(): string
    {
        $db = Database::connection();

        $max = 0;
        foreach (['medicines' => 'barcode', 'medicine_barcodes' => 'barcode'] as $table => $column) {
            $stmt = $db->query("SELECT {$column} AS code FROM {$table} WHERE {$column} LIKE 'APO%'");
            foreach ($stmt->fetchAll() as $row) {
                if (preg_match('/^APO(\d{6,})$/', (string) $row['code'], $m)) {
                    $max = max($max, (int) $m[1]);
                }
            }
        }

        return 'APO' . str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
    }
}
