<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

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
        config()->set('livewire-tables.use_json_translations', true);
        View::composer('*', function () {
            App::setLocale(Auth::user()?->locale ?? session('locale', config('app.locale', 'en')));
        });
        //
    }
}
