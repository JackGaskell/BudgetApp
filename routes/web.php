<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordsController;
use App\Http\Controllers\RecurringController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/records', [RecordsController::class, 'index'])->name('records.index');

    Route::get('/recurring', [RecurringController::class, 'index'])->name('recurring.index');
    Route::get('/recurring/expenses/{recurringExpense}/edit', [RecurringController::class, 'editExpense'])->name('recurring.expenses.edit');
    Route::patch('/recurring/expenses/{recurringExpense}', [RecurringController::class, 'updateExpense'])->name('recurring.expenses.update');
    Route::get('/recurring/income/{recurringIncome}/edit', [RecurringController::class, 'editIncome'])->name('recurring.income.edit');
    Route::patch('/recurring/income/{recurringIncome}', [RecurringController::class, 'updateIncome'])->name('recurring.income.update');
    Route::delete('/recurring/expenses/{recurringExpense}', [RecurringController::class, 'destroyExpense'])->name('recurring.expenses.destroy');
    Route::delete('/recurring/income/{recurringIncome}', [RecurringController::class, 'destroyIncome'])->name('recurring.income.destroy');
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::patch('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::post('/income', [IncomeController::class, 'store'])->name('income.store');
    Route::get('/income/{income}/edit', [IncomeController::class, 'edit'])->name('income.edit');
    Route::patch('/income/{income}', [IncomeController::class, 'update'])->name('income.update');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::delete('/income/{income}', [IncomeController::class, 'destroy'])->name('income.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
