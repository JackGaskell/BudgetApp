<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
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
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
