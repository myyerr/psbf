<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
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
        // PASTIKAN BARIS INI TIDAK DIKOMENTARI
        // Ini yang mendaftarkan route /broadcasting/auth
        Broadcast::routes();

        // PASTIKAN BARIS INI JUGA TIDAK DIKOMENTARI
        // Ini yang memuat file routes/channels.php Anda
        require base_path('routes/channels.php');
    }
}