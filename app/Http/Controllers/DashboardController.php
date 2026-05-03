<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $today = now()->toDateString();
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $actualIncome = $user->incomes()
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->whereDate('date', '<=', $today)
            ->sum('amount');

        $scheduledIncome = $user->incomes()
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->whereDate('date', '>', $today)
            ->sum('amount');

        $actualExpenses = $user->expenses()
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->whereDate('date', '<=', $today)
            ->sum('amount');

        $scheduledExpenses = $user->expenses()
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->whereDate('date', '>', $today)
            ->sum('amount');

        $totalMonthIncome = $actualIncome + $scheduledIncome;
        $totalMonthExpenses = $actualExpenses + $scheduledExpenses;
        $currentBalance = $actualIncome - $actualExpenses;
        $projectedEndOfMonthBalance = $totalMonthIncome - $totalMonthExpenses;
        $safeToSpend = $projectedEndOfMonthBalance;
        $projectedOverspendAmount = $projectedEndOfMonthBalance < 0
            ? abs($projectedEndOfMonthBalance)
            : null;

        $daysPassedInMonth = now()->day;
        $daysInMonth = now()->daysInMonth;
        $daysLeftInMonth = $daysInMonth - $daysPassedInMonth;
        $averageDailySpend = $daysPassedInMonth > 0 ? $actualExpenses / $daysPassedInMonth : 0;
        $daysUntilBroke = $averageDailySpend > 0 ? floor($currentBalance / $averageDailySpend) : null;
        $dailyBudgetRemaining = $daysLeftInMonth > 0
            ? $safeToSpend / $daysLeftInMonth
            : null;

        $recentExpenses = $user->expenses()
            ->latest('date')
            ->limit(5)
            ->get();

        $categoriesWithTotals = $user->expenses()
            ->select('category', DB::raw('SUM(amount) as total'))
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->whereDate('date', '<=', $today)
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(function ($category) use ($actualExpenses) {
                $category->percentage = $actualExpenses > 0
                    ? ($category->total / $actualExpenses) * 100
                    : 0;

                return $category;
            });

        return view('dashboard', [
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
            'days_until_broke' => $daysUntilBroke,
            'daily_budget_remaining' => $dailyBudgetRemaining,
            'categories_with_totals' => $categoriesWithTotals,
            'recent_expenses' => $recentExpenses,
            'expense_categories' => Expense::CATEGORIES,
        ]);
    }
}
