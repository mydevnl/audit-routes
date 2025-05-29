<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes;

use Illuminate\Support\ServiceProvider;
use MyDev\AuditRoutes\Examples\Commands\AdvancedReportingCommand;
use MyDev\AuditRoutes\Examples\Commands\AuthenticatedCommand;
use MyDev\AuditRoutes\Examples\Commands\PhpUnitCoverageCommand;
use MyDev\AuditRoutes\Examples\Commands\PhpUnitDetailedCoverageCommand;
use MyDev\AuditRoutes\Examples\Commands\ScopedBindingCommand;

class AuditRoutesServiceProvider extends ServiceProvider
{
    /** @return void */
    public function boot(): void
    {
        $basePath = __DIR__ . '/../';
        $configPath = $basePath . 'config/audit-routes.php';
        $viewsPath = $basePath . 'resources/views';

        $this->registerPublishing($configPath);
        $this->mergeConfigFrom($configPath, 'audit-routes');

        $this->loadViewsFrom($viewsPath, 'audit-routes');

        $this->commands([
            AdvancedReportingCommand::class,
            AuthenticatedCommand::class,
            ScopedBindingCommand::class,
            PhpUnitCoverageCommand::class,
            PhpUnitDetailedCoverageCommand::class,
        ]);
    }

    /**
     * @param string $configPath
     * @return void
     */
    protected function registerPublishing(string $configPath): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->publishes([$configPath => getcwd() . '/config/audit-routes.php'], 'audit-routes-config');
    }
}
