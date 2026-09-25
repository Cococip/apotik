<?php

namespace App\Services;

class CsvExporter
{
    /**
     * Streams a CSV response and terminates the request (§24 export CSV).
     * @param string[] $headers
     * @param array<int, array<int, mixed>> $rows
     */
    public static function stream(string $filename, array $headers, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel renders Indonesian text correctly
        fputcsv($out, $headers, ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($out, $row, ',', '"', '\\');
        }
        fclose($out);
        exit;
    }
}
