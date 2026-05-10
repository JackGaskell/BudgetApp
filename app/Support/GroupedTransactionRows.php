<?php

namespace App\Support;

use App\Models\Expense;
use App\Models\Income;
use Illuminate\Support\Collection;

/**
 * Groups multiple month occurrences that share the same recurring rule for cleaner tables.
 */
final class GroupedTransactionRows
{
    /**
     * @param  Collection<int, Expense>  $expenses
     * @return Collection<int, array{kind: 'single', expense: Expense}|array{kind: 'group', items: Collection<int, Expense>}>
     */
    public static function forExpenses(Collection $expenses): Collection
    {
        $out = collect();
        $grouped = $expenses->whereNotNull('recurring_expense_id')->groupBy('recurring_expense_id');

        foreach ($grouped as $items) {
            /** @var Collection<int, Expense> $items */
            $items = $items->sortByDesc(fn (Expense $e) => $e->date)->values();
            if ($items->count() >= 2) {
                $out->push(['kind' => 'group', 'items' => $items]);
            } else {
                $out->push(['kind' => 'single', 'expense' => $items->first()]);
            }
        }

        foreach ($expenses->whereNull('recurring_expense_id') as $expense) {
            $out->push(['kind' => 'single', 'expense' => $expense]);
        }

        return $out->sortByDesc(function (array $row) {
            if ($row['kind'] === 'group') {
                return $row['items']->max('date');
            }

            return $row['expense']->date;
        })->values();
    }

    /**
     * @param  Collection<int, Income>  $incomes
     * @return Collection<int, array{kind: 'single', income: Income}|array{kind: 'group', items: Collection<int, Income>}>
     */
    public static function forIncomes(Collection $incomes): Collection
    {
        $out = collect();
        $grouped = $incomes->whereNotNull('recurring_income_id')->groupBy('recurring_income_id');

        foreach ($grouped as $items) {
            /** @var Collection<int, Income> $items */
            $items = $items->sortByDesc(fn (Income $i) => $i->date)->values();
            if ($items->count() >= 2) {
                $out->push(['kind' => 'group', 'items' => $items]);
            } else {
                $out->push(['kind' => 'single', 'income' => $items->first()]);
            }
        }

        foreach ($incomes->whereNull('recurring_income_id') as $income) {
            $out->push(['kind' => 'single', 'income' => $income]);
        }

        return $out->sortByDesc(function (array $row) {
            if ($row['kind'] === 'group') {
                return $row['items']->max('date');
            }

            return $row['income']->date;
        })->values();
    }

    /**
     * @param  Collection<int, array{kind: 'single', expense: Expense}|array{kind: 'group', items: Collection<int, Expense>}>  $rows
     * @return Collection<int, Expense>
     */
    public static function flattenExpenseRows(Collection $rows): Collection
    {
        return $rows->flatMap(function (array $row) {
            if ($row['kind'] === 'group') {
                return $row['items'];
            }

            return collect([$row['expense']]);
        });
    }

    /**
     * @param  Collection<int, array{kind: 'single', income: Income}|array{kind: 'group', items: Collection<int, Income>}>  $rows
     * @return Collection<int, Income>
     */
    public static function flattenIncomeRows(Collection $rows): Collection
    {
        return $rows->flatMap(function (array $row) {
            if ($row['kind'] === 'group') {
                return $row['items'];
            }

            return collect([$row['income']]);
        });
    }
}
