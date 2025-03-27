<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Examples\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use MyDev\AuditRoutes\Aggregators\AverageScore;
use MyDev\AuditRoutes\Aggregators\ConditionedCumulative;
use MyDev\AuditRoutes\Aggregators\FailedPercentage;
use MyDev\AuditRoutes\Aggregators\SuccessPercentage;
use MyDev\AuditRoutes\Auditors\TestAuditor;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Output\Export\ExportFactory;
use MyDev\AuditRoutes\Output\Export\ExportInterface;
use MyDev\AuditRoutes\Output\OutputFactory;

class TestCoverageCommand extends Command
{
    protected $signature = 'route:audit-test-coverage {--benchmark=1} {--export=} {--filename=}';
    protected $description = 'Run Test Coverage auditing for Laravel routes';

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
            ->setBenchmark((int) $this->option('benchmark'))
            ->run([TestAuditor::make()]);

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
            new FailedPercentage('Uncovered rate'),
            new SuccessPercentage('Covered rate'),
            new AverageScore('Average coverage'),
        ]);
    }
}
