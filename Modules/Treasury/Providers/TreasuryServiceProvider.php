<?php

namespace Modules\Treasury\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class TreasuryServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'Treasury';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'treasury';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
        $this->registerServices();
    }

    /**
     * Register Treasury services
     *
     * @return void
     */
    protected function registerServices()
    {
        // Register Repository
        $this->app->singleton(
            \Modules\Treasury\Repositories\TreasuryRepository::class,
            \Modules\Treasury\Repositories\TreasuryRepository::class
        );

        // Register Chart Service
        $this->app->singleton(
            \Modules\Treasury\Services\TreasuryChartService::class,
            function ($app) {
                return new \Modules\Treasury\Services\TreasuryChartService(
                    $app->make(\Modules\Treasury\Repositories\TreasuryRepository::class),
                    $app->make(\App\Utils\Util::class)
                );
            }
        );

        // Register Internal Transfer Service
        $this->app->singleton(
            \Modules\Treasury\Services\InternalTransferService::class,
            function ($app) {
                return new \Modules\Treasury\Services\InternalTransferService(
                    $app->make(\Modules\Treasury\Repositories\TreasuryRepository::class),
                    $app->make(\App\Utils\Util::class),
                    $app->make(\App\Utils\TransactionUtil::class)
                );
            }
        );

        // Register Transaction Service
        $this->app->singleton(
            \Modules\Treasury\Services\TreasuryTransactionService::class,
            function ($app) {
                return new \Modules\Treasury\Services\TreasuryTransactionService(
                    $app->make(\Modules\Treasury\Repositories\TreasuryRepository::class),
                    $app->make(\App\Utils\TransactionUtil::class),
                    $app->make(\App\Utils\BusinessUtil::class)
                );
            }
        );

        // Register Validation Service
        $this->app->singleton(
            \Modules\Treasury\Services\TreasuryValidationService::class,
            \Modules\Treasury\Services\TreasuryValidationService::class
        );

        // Register Main Treasury Service
        $this->app->singleton(
            \Modules\Treasury\Services\TreasuryService::class,
            function ($app) {
                return new \Modules\Treasury\Services\TreasuryService(
                    $app->make(\Modules\Treasury\Repositories\TreasuryRepository::class),
                    $app->make(\App\Utils\ModuleUtil::class),
                    $app->make(\App\Utils\TransactionUtil::class),
                    $app->make(\App\Utils\Util::class),
                    $app->make(\Modules\Treasury\Services\TreasuryChartService::class)
                );
            }
        );
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
