<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Examples\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use MyDev\AuditRoutes\Aggregators\AverageScore;
use MyDev\AuditRoutes\Aggregators\ConditionedCumulative;
use MyDev\AuditRoutes\Aggregators\FailedPercentage;
use MyDev\AuditRoutes\Aggregators\Group;
use MyDev\AuditRoutes\Aggregators\HighestScore;
use MyDev\AuditRoutes\Aggregators\LowestScore;
use MyDev\AuditRoutes\Aggregators\MedianScore;
use MyDev\AuditRoutes\Aggregators\ModeScore;
use MyDev\AuditRoutes\Aggregators\SuccessPercentage;
use MyDev\AuditRoutes\Aggregators\TotalAroundBenchmark;
use MyDev\AuditRoutes\Aggregators\TotalBetweenScores;
use MyDev\AuditRoutes\Auditors\MiddlewareAuditor;
use MyDev\AuditRoutes\Auditors\PermissionAuditor;
use MyDev\AuditRoutes\Auditors\PolicyAuditor;
use MyDev\AuditRoutes\Auditors\ScopedBindingAuditor;
use MyDev\AuditRoutes\Auditors\TestAuditor;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Output\Export\ExportFactory;
use MyDev\AuditRoutes\Output\Export\ExportInterface;
use MyDev\AuditRoutes\Output\OutputFactory;
use MyDev\AuditRoutes\Routes\RouteInterface;

class ScopedBindingCommand extends Command
{
    protected $signature = 'route:audit-scoped-bindings {--export=} {--filename=}';
    protected $description = 'Run Scoped Binding Reporting auditing for Laravel routes';

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
            ->run([ScopedBindingAuditor::class]);

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
            new FailedPercentage('Unscoped rate'),
            new SuccessPercentage('Scoped rate'),
            new AverageScore('Average scoping'),
        ]);
    }
}
