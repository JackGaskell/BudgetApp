<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public const CATEGORIES = [
        'Housing & Utilities',
        'Food',
        'Transport',
        'Health & Fitness',
        'Education',
        'Personal & Clothing',
        'Entertainment & Subscriptions',
        'Debt Repayments',
        'Savings & Emergency',
        'Miscellaneous',
    ];

    protected $fillable = [
        'name',
        'amount',
        'category',
        'date',
        'recurring_expense_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recurringExpense(): BelongsTo
    {
        return $this->belongsTo(RecurringExpense::class);
    }
}
