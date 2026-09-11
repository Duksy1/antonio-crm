<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class QuoteNumberGenerator
{
    public function next(?int $year = null): string
    {
        $year ??= now()->year;

        $counter = DB::selectOne(<<<'SQL'
            INSERT INTO quote_number_counters (year, last_number, created_at, updated_at)
            VALUES (
                ?,
                COALESCE((
                    SELECT MAX(CAST(SUBSTRING(number FROM '([0-9]+)$') AS BIGINT))
                    FROM quotes
                    WHERE number ~ ?
                ), 0) + 1,
                CURRENT_TIMESTAMP,
                CURRENT_TIMESTAMP
            )
            ON CONFLICT (year) DO UPDATE
            SET
                last_number = quote_number_counters.last_number + 1,
                updated_at = CURRENT_TIMESTAMP
            RETURNING last_number
            SQL, [$year, "^AF-{$year}-[0-9]+$"]);

        if ($counter === null) {
            throw new RuntimeException('Nije moguće generirati broj ponude.');
        }

        return sprintf('AF-%d-%04d', $year, (int) $counter->last_number);
    }
}
