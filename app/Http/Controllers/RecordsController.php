<?php

namespace App\Http\Controllers;

use App\Services\RecurringMaterializer;
use App\Support\GroupedTransactionRows;
use App\Support\ViewMonth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecordsController extends Controller
{
    public function index(Request $request, RecurringMaterializer $materializer): View
    {
        $user = $request->user();

        [$year, $month] = ViewMonth::fromRequest($request);
        $materializer->materializeMonth($user, $year, $month);

        $splitDate = ViewMonth::splitDateForActualVsScheduled($year, $month);

        $expenses = $user->expenses()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->latest('date')
            ->get();

        $incomes = $user->incomes()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->latest('date')
            ->get();

        $monthCursor = Carbon::create($year, $month, 1);
        $prevMonth = $monthCursor->copy()->subMonth();
        $nextMonth = $monthCursor->copy()->addMonth();

        return view('records', [
            'view_year' => $year,
            'view_month' => $month,
            'view_month_label' => $monthCursor->translatedFormat('F Y'),
            'prev_period_params' => ViewMonth::queryParams($prevMonth->year, $prevMonth->month),
            'next_period_params' => ViewMonth::queryParams($nextMonth->year, $nextMonth->month),
            'split_date' => $splitDate,
            'expenses' => $expenses,
            'expense_rows' => GroupedTransactionRows::forExpenses($expenses),
            'incomes' => $incomes,
            'income_rows' => GroupedTransactionRows::forIncomes($incomes),
        ]);
    }
}
