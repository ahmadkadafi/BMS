<?php

namespace App\Providers;

use App\Models\Resor;
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
        View::composer('partials.sidebar', function ($view) {
            $resors = Resor::orderBy('nama')->get();
            $view->with('resors', $resors);
        });
    }
}
