<?php

namespace App\Providers;

use App\Repositories\Setting\SettingRepository;
use App\Repositories\Setting\SettingRepositoryInterface;
use App\Services\Reviews\Providers\ReviewsProviderInterface;
use App\Services\Reviews\Providers\YandexReviewsProvider;
use App\Services\Reviews\ReviewsService;
use App\Services\Reviews\ReviewsServiceInterface;
use App\Services\Settings\SettingService;
use App\Services\Settings\SettingServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SettingRepositoryInterface::class, SettingRepository::class);
        $this->app->bind(ReviewsProviderInterface::class, YandexReviewsProvider::class);
        $this->app->bind(ReviewsServiceInterface::class, ReviewsService::class);
        $this->app->bind(SettingServiceInterface::class, SettingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
