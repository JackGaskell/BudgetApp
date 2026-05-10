<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringIncome extends Model
{
    public const FREQUENCY_MONTHLY = 'monthly';

    public const FREQUENCY_WEEKLY = 'weekly';

    protected $fillable = [
        'name',
        'amount',
        'frequency',
        'day_of_month',
        'day_of_week',
        'starts_on',
        'ends_on',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function isWeekly(): bool
    {
        return $this->frequency === self::FREQUENCY_WEEKLY;
    }

    public function isMonthly(): bool
    {
        return $this->frequency === self::FREQUENCY_MONTHLY;
    }
}
