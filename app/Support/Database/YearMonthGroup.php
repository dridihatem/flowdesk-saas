<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;

final class YearMonthGroup
{
    /**
     * SQL expression for grouping timestamps by calendar month (YYYY-MM), driver-safe.
     */
    public static function column(string $column = 'created_at'): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }
}
