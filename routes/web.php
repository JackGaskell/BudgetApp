<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordsController;
use App\Http\Controllers\StudentFundingPlanController;
use App\Support\ViewMonth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/records', [RecordsController::class, 'index'])->name('records.index');

    Route::get('/recurring', function () {
        return redirect()->route('records.index', ViewMonth::queryParams(now()->year, now()->month));
    })->name('recurring.index');
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::patch('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::post('/income', [IncomeController::class, 'store'])->name('income.store');
    Route::get('/income/{income}/edit', [IncomeController::class, 'edit'])->name('income.edit');
    Route::patch('/income/{income}', [IncomeController::class, 'update'])->name('income.update');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::delete('/income/{income}', [IncomeController::class, 'destroy'])->name('income.destroy');

    Route::post('/student/funding-plan', [StudentFundingPlanController::class, 'store'])->name('student.funding-plan.store');
    Route::delete('/student/funding-plan', [StudentFundingPlanController::class, 'destroy'])->name('student.funding-plan.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
