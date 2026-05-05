<?php

namespace Peppermint\Changelog;

use Illuminate\Support\ServiceProvider;
use Peppermint\Changelog\Console\InstallCommand;
use Peppermint\Changelog\Services\ChangelogService;

class ChangelogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/changelog.php', 'changelog');

        $this->app->singleton(ChangelogService::class);
    }

    public function boot(): void
    {
        // Migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Routes
        if (config('changelog.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/changelog.php');
        }

        // Publishable assets + Console-Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);

            // Config
            $this->publishes([
                __DIR__.'/../config/changelog.php' => config_path('changelog.php'),
            ], 'changelog-config');

            // React + shadcn/ui stubs
            $this->publishes([
                __DIR__.'/../stubs/react-shadcn/components/' => resource_path('js/components/'),
                __DIR__.'/../stubs/react-shadcn/pages/' => resource_path('js/pages/'),
            ], 'changelog-react');

            // Vue + shadcn/ui (Reka UI) stubs
            $this->publishes([
                __DIR__.'/../stubs/vue-shadcn/components/' => resource_path('js/components/'),
                __DIR__.'/../stubs/vue-shadcn/pages/' => resource_path('js/pages/'),
            ], 'changelog-vue');
        }
    }
}
