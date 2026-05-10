<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RecurringMaterializer;
use App\Support\GroupedTransactionRows;
use App\Support\ViewMonth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, RecurringMaterializer $materializer): View
    {
        /** @var User $user */
        $user = Auth::user();

        [$year, $month] = ViewMonth::fromRequest($request);
        $materializer->materializeMonth($user, $year, $month);

        $splitDate = ViewMonth::splitDateForActualVsScheduled($year, $month);
        $isCurrentMonth = ViewMonth::isCurrentMonth($year, $month);

        $actualIncome = $user->incomes()
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereDate('date', '<=', $splitDate)
            ->sum('amount');

        $scheduledIncome = $user->incomes()
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereDate('date', '>', $splitDate)
            ->sum('amount');

        $actualExpenses = $user->expenses()
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereDate('date', '<=', $splitDate)
            ->sum('amount');

        $scheduledExpenses = $user->expenses()
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereDate('date', '>', $splitDate)
            ->sum('amount');

        $totalMonthIncome = $actualIncome + $scheduledIncome;
        $totalMonthExpenses = $actualExpenses + $scheduledExpenses;
        $currentBalance = $actualIncome - $actualExpenses;
        $projectedEndOfMonthBalance = $totalMonthIncome - $totalMonthExpenses;
        $safeToSpend = $projectedEndOfMonthBalance;
        $projectedOverspendAmount = $projectedEndOfMonthBalance < 0
            ? abs($projectedEndOfMonthBalance)
            : null;

        $viewMonthStart = Carbon::create($year, $month, 1);
        $daysInMonth = $viewMonthStart->daysInMonth;

        if ($isCurrentMonth) {
            $daysPassedInMonth = now()->day;
        } elseif ($viewMonthStart->lt(now()->startOfMonth())) {
            $daysPassedInMonth = $daysInMonth;
        } else {
            $daysPassedInMonth = 0;
        }

        $spendingPaceDailyAverage = $daysPassedInMonth > 0
            ? $actualExpenses / $daysPassedInMonth
            : null;

        if ($isCurrentMonth) {
            $daysLeftInMonth = max(0, $daysInMonth - now()->day);
            $dailyBudgetRemaining = $daysLeftInMonth > 0 ? $safeToSpend / $daysLeftInMonth : null;
        } else {
            $dailyBudgetRemaining = null;
        }

        $recentExpensePool = $user->expenses()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->latest('date')
            ->limit(40)
            ->get();
        $recentExpenseRows = GroupedTransactionRows::forExpenses($recentExpensePool)->take(5);

        $actualCategoryTotals = $user->expenses()
            ->select('category', DB::raw('SUM(amount) as total'))
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereDate('date', '<=', $splitDate)
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        $scheduledCategoryTotals = $user->expenses()
            ->select('category', DB::raw('SUM(amount) as total'))
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereDate('date', '>', $splitDate)
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        $monthSpendForBreakdown = $actualExpenses + $scheduledExpenses;
        $categoryNames = $actualCategoryTotals->keys()
            ->merge($scheduledCategoryTotals->keys())
            ->unique()
            ->values();

        $categoriesWithTotals = $categoryNames
            ->map(function (string $category) use ($actualCategoryTotals, $scheduledCategoryTotals, $monthSpendForBreakdown) {
                $actual = (float) ($actualCategoryTotals->get($category)->total ?? 0);
                $scheduled = (float) ($scheduledCategoryTotals->get($category)->total ?? 0);
                $total = $actual + $scheduled;

                return (object) [
                    'category' => $category,
                    'total' => $total,
                    'actual_total' => $actual,
                    'scheduled_total' => $scheduled,
                    'percentage' => $monthSpendForBreakdown > 0
                        ? ($total / $monthSpendForBreakdown) * 100
                        : 0,
                ];
            })
            ->filter(fn ($row) => $row->total > 0)
            ->sortByDesc('total')
            ->values();

        $monthCursor = Carbon::create($year, $month, 1);
        $prevMonth = $monthCursor->copy()->subMonth();
        $nextMonth = $monthCursor->copy()->addMonth();

        return view('dashboard', [
            'view_year' => $year,
            'view_month' => $month,
            'view_month_label' => $monthCursor->translatedFormat('F Y'),
            'is_current_month' => $isCurrentMonth,
            'split_date' => $splitDate,
            'prev_period_params' => ViewMonth::queryParams($prevMonth->year, $prevMonth->month),
            'next_period_params' => ViewMonth::queryParams($nextMonth->year, $nextMonth->month),
            'actual_income' => $actualIncome,
            'scheduled_income' => $scheduledIncome,
            'actual_expenses' => $actualExpenses,
            'scheduled_expenses' => $scheduledExpenses,
            'total_month_income' => $totalMonthIncome,
            'total_month_expenses' => $totalMonthExpenses,
            'current_balance' => $currentBalance,
            'projected_end_of_month_balance' => $projectedEndOfMonthBalance,
            'safe_to_spend' => $safeToSpend,
            'projected_overspend_amount' => $projectedOverspendAmount,
            'daily_budget_remaining' => $dailyBudgetRemaining,
            'spending_pace_daily_average' => $spendingPaceDailyAverage,
            'categories_with_totals' => $categoriesWithTotals,
            'recent_expense_rows' => $recentExpenseRows,
            'recent_expenses_for_dialogs' => GroupedTransactionRows::flattenExpenseRows($recentExpenseRows),
        ]);
    }
}
