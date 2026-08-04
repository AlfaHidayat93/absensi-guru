<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
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
        // Otomatis jalankan migrasi & seeder jika tabel baru atau kolom baru belum ada di hosting
        try {
            if (!app()->runningInConsole()) {
                if (!Schema::hasTable('subjects') || 
                    !Schema::hasTable('attendances') || 
                    !Schema::hasTable('grades') || 
                    !Schema::hasTable('grade_settings') || 
                    !Schema::hasColumn('grades', 'poin_sikap') || 
                    !Schema::hasColumn('users', 'homeroom_class')) {
                    
                    Artisan::call('migrate', ['--force' => true]);
                    Artisan::call('db:seed', ['--force' => true]);
                    Artisan::call('optimize:clear');
                }
            }
        } catch (\Throwable $e) {
            // Biarkan lewat jika database dalam keadaan terkunci sementara
        }
    }
}
