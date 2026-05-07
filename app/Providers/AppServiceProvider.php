<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Booking;
use App\Observers\BookingObserver;
use App\Models\Interaction;
use App\Observers\InteractionObserver;
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
        Interaction::observe(InteractionObserver::class);
        Booking::observe(BookingObserver::class);
    }
}
