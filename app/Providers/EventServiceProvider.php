<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\Feature;
use App\Models\FuelType;
use App\Models\SeoPage;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Observers\CatalogCacheObserver;
use App\Observers\SlugRedirectObserver;
use App\Observers\VehicleObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        Vehicle::observe([SlugRedirectObserver::class, CatalogCacheObserver::class, VehicleObserver::class]);

        foreach ([Brand::class, Category::class, VehicleModel::class, FuelType::class, City::class, Feature::class, SeoPage::class] as $model) {
            $model::observe([SlugRedirectObserver::class, CatalogCacheObserver::class]);
        }
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
