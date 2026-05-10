<?php

namespace Tests\Unit;

use App\Support\ViewMonth;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ViewMonthTest extends TestCase
{
    #[Test]
    public function it_falls_back_to_current_month_when_query_month_is_invalid(): void
    {
        $this->travelTo('2026-06-15 10:00:00');

        $request = Request::create('/dashboard', 'GET', ['month' => 13, 'year' => 2026]);
        [$year, $month] = ViewMonth::fromRequest($request);

        $this->assertSame(2026, $year);
        $this->assertSame(6, $month);
    }

    #[Test]
    public function it_falls_back_to_current_year_when_query_year_is_out_of_range(): void
    {
        $this->travelTo('2026-06-15 10:00:00');

        $request = Request::create('/dashboard', 'GET', ['month' => 3, 'year' => 1990]);
        [$year, $month] = ViewMonth::fromRequest($request);

        $this->assertSame(2026, $year);
        $this->assertSame(3, $month);
    }

    #[Test]
    public function split_date_for_past_month_is_end_of_that_month(): void
    {
        $this->travelTo('2026-05-09 10:00:00');

        $split = ViewMonth::splitDateForActualVsScheduled(2026, 4);

        $this->assertSame('2026-04-30', $split);
    }

    #[Test]
    public function split_date_for_future_month_is_day_before_month_starts(): void
    {
        $this->travelTo('2026-05-09 10:00:00');

        $split = ViewMonth::splitDateForActualVsScheduled(2026, 7);

        $this->assertSame('2026-06-30', $split);
    }

    #[Test]
    public function split_date_for_current_month_is_today(): void
    {
        $this->travelTo('2026-05-09 10:00:00');

        $split = ViewMonth::splitDateForActualVsScheduled(2026, 5);

        $this->assertSame('2026-05-09', $split);
    }

    #[Test]
    public function query_params_returns_year_and_month(): void
    {
        $this->assertSame(
            ['year' => 2027, 'month' => 2],
            ViewMonth::queryParams(2027, 2)
        );
    }
}
