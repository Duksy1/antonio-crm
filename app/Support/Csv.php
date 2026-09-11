<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class Csv
{
    /**
     * Streama CSV datoteku s UTF-8 BOM-om i točkom-zarezom (Excel-friendly).
     *
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public static function stream(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headings, ';');

            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
