<?php

namespace App\Support;

class Money
{
    public const LOCALE = 'en_GB';

    public const CURRENCY = 'GBP';

    /**
     * Format a numeric amount as pound sterling (GBP) for display.
     */
    public static function format(mixed $amount): string
    {
        $value = match (true) {
            $amount === null, $amount === '' => 0.0,
            default => (float) $amount,
        };

        if (extension_loaded('intl')) {
            $formatter = new \NumberFormatter(self::LOCALE, \NumberFormatter::CURRENCY);
            $formatted = $formatter->formatCurrency($value, self::CURRENCY);

            if ($formatted !== false) {
                return $formatted;
            }
        }

        return self::fallback($value);
    }

    private static function fallback(float $value): string
    {
        $negative = $value < 0;
        $formatted = '£'.number_format(abs($value), 2, '.', ',');

        return $negative ? '-'.$formatted : $formatted;
    }
}
