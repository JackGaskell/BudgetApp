<?php

namespace App\View\Composers;

use App\Models\Expense;
use App\Support\ViewMonth;
use Carbon\Carbon;
use Illuminate\View\View;

class BudgetLayoutComposer
{
    public function compose(View $view): void
    {
        $request = request();
        [$year, $month] = ViewMonth::fromRequest($request);
        $isCurrentMonth = ViewMonth::isCurrentMonth($year, $month);
        $monthCursor = Carbon::create($year, $month, 1);
        $defaultDate = $isCurrentMonth
            ? now()->toDateString()
            : $monthCursor->toDateString();

        $bag = $request->session()->get('errors');
        $hasExpenseErrors = $bag && (
            $bag->has('name') || $bag->has('amount') || $bag->has('category') || $bag->has('date')
        );
        $hasIncomeErrors = $bag && (
            $bag->has('income_name') || $bag->has('income_amount') || $bag->has('income_date')
        );
        $openModal = (bool) ($hasExpenseErrors || $hasIncomeErrors);
        $tab = ($hasIncomeErrors && ! $hasExpenseErrors) ? 'income' : 'expense';

        $view->with([
            'add_modal_default_date' => $defaultDate,
            'add_modal_expense_categories' => Expense::CATEGORIES,
            'add_modal_open' => $openModal,
            'add_modal_tab' => $tab,
        ]);
    }
}
