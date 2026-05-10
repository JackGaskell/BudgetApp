<?php

namespace App\Providers;

use App\View\Composers\BudgetLayoutComposer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.budget', BudgetLayoutComposer::class);

        Blade::directive('money', function (string $expression): string {
            return "<?php echo e(\\App\\Support\\Money::format({$expression})); ?>";
        });
    }
}
