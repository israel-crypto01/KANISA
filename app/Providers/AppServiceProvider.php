<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        try {
            if (!cache()->has('church_default_departments_seeded')) {
                \App\Models\Department::ensureDefaultsExist();
                if (\App\Models\Department::count() >= 5) {
                    cache()->forever('church_default_departments_seeded', true);
                }
            }
        } catch (\Throwable $e) {
            // Ignore during migrations or when database is not yet connected
        }
    }
}
