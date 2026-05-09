<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\RecurringMaterializer;
use Illuminate\Console\Command;

class RecurringMaterializeCommand extends Command
{
    protected $signature = 'recurring:materialize {--months=3 : Months ahead to materialize (including the current month)}';

    protected $description = 'Create income/expense rows from monthly recurring rules for the current rolling window';

    public function handle(RecurringMaterializer $materializer): int
    {
        $monthsAhead = max(0, (int) $this->option('months') - 1);

        $count = 0;
        User::query()->orderBy('id')->chunk(100, function ($users) use ($materializer, $monthsAhead, &$count) {
            foreach ($users as $user) {
                $materializer->materializeUpcomingMonths($user, $monthsAhead);
                $count++;
            }
        });

        $this->info("Materialized recurring rows for {$count} user(s).");

        return self::SUCCESS;
    }
}
