<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait SortsAndPaginates
{
    /**
     * @param  array<int, string>  $allowed
     * @return array{0: string, 1: string}
     */
    protected function sortFrom(Request $request, array $allowed, string $default = 'created_at'): array
    {
        $column = $request->string('sort')->toString();
        $column = in_array($column, $allowed, true) ? $column : $default;

        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        return [$column, $direction];
    }

    /**
     * @param  array<int, int>  $allowed
     */
    protected function perPageFrom(Request $request, int $default = 12, array $allowed = [12, 24, 48, 96]): int
    {
        $perPage = (int) $request->input('per_page', $default);

        return in_array($perPage, $allowed, true) ? $perPage : $default;
    }
}
