<?php

namespace Jguapin\ApprovalMapping;

use Illuminate\Support\ServiceProvider;
use Jguapin\ApprovalMapping\Console\InstallApprovalMappingCommand;
use Jguapin\ApprovalMapping\Services\ApprovalMappingService;

class ApprovalMappingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/approval-mapping.php', 'approval-mapping');

        $this->app->singleton(ApprovalMappingService::class, function () {
            return new ApprovalMappingService;
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'approval-mapping');

        $this->publishes([
            __DIR__.'/../config/approval-mapping.php' => config_path('approval-mapping.php'),
        ], 'approval-mapping-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'approval-mapping-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/approval-mapping'),
        ], 'approval-mapping-views');

        $this->publishes([
            __DIR__.'/../resources/js' => resource_path('js/vendor/approval-mapping'),
        ], 'approval-mapping-assets');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallApprovalMappingCommand::class,
            ]);
        }
    }
}
