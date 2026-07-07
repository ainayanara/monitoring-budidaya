<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('is-mentor', fn ($user) => $user->peran === 'mentor');
        Gate::define('is-siswa', fn ($user) => $user->peran === 'siswa');
    }
}
