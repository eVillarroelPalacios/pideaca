<?php

namespace App\Providers;

use App\Models\ServiceOrder;
use App\Models\ServiceQuote;
use App\Models\ServiceRating;
use App\Observers\ServiceOrderObserver;
use App\Observers\ServiceQuoteObserver;
use App\Observers\ServiceRatingObserver;
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
        // Avisos push del Módulo B: observan a los modelos, así que se
        // disparan desde la web y desde el API móvil sin tocar controladores.
        ServiceOrder::observe(ServiceOrderObserver::class);
        ServiceQuote::observe(ServiceQuoteObserver::class);
        ServiceRating::observe(ServiceRatingObserver::class);

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
