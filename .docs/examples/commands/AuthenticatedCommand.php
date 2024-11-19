<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Examples\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use MyDev\AuditRoutes\Aggregators\ConditionedCumulative;
use MyDev\AuditRoutes\Aggregators\FailedPercentage;
use MyDev\AuditRoutes\Aggregators\SuccessPercentage;
use MyDev\AuditRoutes\Auditors\MiddlewareAuditor;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Output\ExportFactory;
use MyDev\AuditRoutes\Output\ExportInterface;
use MyDev\AuditRoutes\Output\OutputFactory;
use MyDev\AuditRoutes\Routes\RouteInterface;

class AuthenticatedCommand extends Command
{
    protected $signature = 'route:audit-auth {--export=} {--filename=}';
    protected $description = 'Run Authentication Middleware auditing for Laravel routes';

    /**
     * @param Router $router
     * @return void
     */
    public function __construct(protected Router $router)
    {
        parent::__construct();
    }

    /** @return int */
    public function handle(): int
    {
        $output = OutputFactory::channel($this->output)->setExporter($this->getExporter())->build();

        $result = AuditRoutes::for($this->router->getRoutes()->getRoutes())
            ->setBenchmark(1)
            ->run([
                MiddlewareAuditor::make(['auth'])
                    ->ignoreRoutes(['api.*'])
                    ->setName('MiddlewareAuditor auth'),
                MiddlewareAuditor::make(['auth:sanctum'])
                    ->when(fn(RouteInterface $route): bool => str_starts_with($route->getIdentifier(), 'api'))
                    ->setName('MiddlewareAuditor auth:sanctum'),
            ]);

        return $output->generate($result)->value;
    }

    /** @return null | ExportInterface */
    protected function getExporter(): ?ExportInterface
    {
        return ExportFactory::channel($this->output)->build(
            $this->option('export'),
            $this->option('filename'),
        )?->setAggregators([
            new ConditionedCumulative('Total routes'),
            new FailedPercentage('Guest rate'),
            new SuccessPercentage('Authenticated rate'),
        ]);
    }
}
