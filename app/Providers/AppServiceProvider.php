<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

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
        // Rate Limiter للويبهوك SonarQube
        RateLimiter::for('sonar-webhook', function ($request) {
            // 30 طلب في الدقيقة كفاية للـ CE + إعادة الإرسال
            return [Limit::perMinute(30)->by('sonar-webhook')];
        });
    }
}
