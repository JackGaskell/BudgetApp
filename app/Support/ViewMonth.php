<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

final class ViewMonth
{
    /**
     * @return array{0: int, 1: int}
     */
    public static function fromRequest(Request $request): array
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        if ($month < 1 || $month > 12) {
            $month = now()->month;
        }

        if ($year < 2000 || $year > 2100) {
            $year = now()->year;
        }

        return [$year, $month];
    }

    /**
     * Dates on or before this string (Y-m-d) count as “actual” for the viewed month;
     * later dates in that month are “scheduled”.
     */
    public static function splitDateForActualVsScheduled(int $year, int $month): string
    {
        $viewStart = Carbon::create($year, $month, 1)->startOfDay();
        $viewEnd = $viewStart->copy()->endOfMonth();
        $today = now()->startOfDay();

        if ($viewEnd->lt($today)) {
            return $viewEnd->toDateString();
        }

        if ($viewStart->gt($today)) {
            return $viewStart->copy()->subDay()->toDateString();
        }

        return $today->toDateString();
    }

    public static function isCurrentMonth(int $year, int $month): bool
    {
        return now()->year === $year && now()->month === $month;
    }

    public static function queryParams(int $year, int $month): array
    {
        return ['year' => $year, 'month' => $month];
    }
}
