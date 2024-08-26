<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes;

use Illuminate\Support\ServiceProvider;
use MyDev\AuditRoutes\Auditors\TestAuditor;
use MyDev\AuditRoutes\Examples\Commands\AuditRoutesCommand;

class AuditRoutesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $configPath = __DIR__ . '/../config/audit-routes.php';

        $this->registerPublishing($configPath);
        $this->mergeConfigFrom($configPath, 'audit-routes');

        $this->commands([AuditRoutesCommand::class]);
    }

    public function register(): void
    {
        $this->app->singleton(TestAuditor::class, fn (): TestAuditor => new TestAuditor());
    }

    protected function registerPublishing(string $configPath): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->publishes([$configPath => getcwd() . '/config/audit-routes.php'], 'audit-routes-config');
    }
}
