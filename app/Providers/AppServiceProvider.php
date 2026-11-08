<?php

namespace App\Providers;

use App\Support\CurrencyResolver;
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
        // @money($value) o @money($value, $currency)
        Blade::directive('money', function (string $expression) {
            return "<?php echo e(\\App\\Support\\Money::format({$expression})); ?>";
        });

        View::composer([
            'dashboard',
            'delivery.panel',
            'reports.hogar',
            'reports.hogar-pdf',
            'repartidor.reportes',
        ], function ($view) {
            $view->with('currency', CurrencyResolver::current());
        });
    }
}
