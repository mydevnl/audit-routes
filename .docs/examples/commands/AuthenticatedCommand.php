<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Examples\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use MyDev\AuditRoutes\Aggregators\ConditionedTotal;
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
    protected $description = 'Run security auditing for Laravel routes';

    /**
     * @param Router $router
     * @return void
     */
    public function __construct(protected Router $router)
    {
        parent::__construct();
    }

    /** @return void */
    public function handle(): void
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

        $output->generate($result);
    }

    /** @return ?ExportInterface */
    protected function getExporter(): ?ExportInterface
    {
        return ExportFactory::channel($this->output)->build(
            $this->option('export'),
            $this->option('filename'),
        )?->setAggregators([
            new ConditionedTotal('Total routes'),
            new FailedPercentage('Guest rate'),
            new SuccessPercentage('Authenticated rate'),
        ]);
    }
}
