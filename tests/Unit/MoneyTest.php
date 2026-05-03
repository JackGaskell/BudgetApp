<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_formats_positive_amounts_with_pound_symbol(): void
    {
        $formatted = Money::format(1234.5);

        $this->assertStringContainsString('£', $formatted);
        $this->assertStringContainsString('234', $formatted);
        $this->assertStringContainsString('50', $formatted);
    }

    public function test_it_formats_zero(): void
    {
        $formatted = Money::format(0);

        $this->assertStringContainsString('£', $formatted);
        $this->assertStringContainsString('0', $formatted);
    }
}
