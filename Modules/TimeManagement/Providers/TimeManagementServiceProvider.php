<?php

namespace Modules\TimeManagement\Providers;

use Illuminate\Support\ServiceProvider;

class TimeManagementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerConfig();
        $this->registerTranslations();
        $this->registerViews();
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }

    public function register(): void
    {
        // Nothing to register for now
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('timemanagement.php'),
        ], 'config');

        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php', 'timemanagement'
        );
    }

    protected function registerViews(): void
    {
        $sourcePath = __DIR__ . '/../Resources/views';
        $this->loadViewsFrom($sourcePath, 'timemanagement');
        $this->publishes([
            $sourcePath => resource_path('views/modules/timemanagement'),
        ], 'views');
    }

    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'timemanagement');
    }
}
