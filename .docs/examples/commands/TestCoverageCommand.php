<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Examples\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use MyDev\AuditRoutes\Aggregators\AverageScore;
use MyDev\AuditRoutes\Aggregators\FailedPercentage;
use MyDev\AuditRoutes\Aggregators\SuccessPercentage;
use MyDev\AuditRoutes\Auditors\TestAuditor;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Output\ExportFactory;
use MyDev\AuditRoutes\Output\ExportInterface;
use MyDev\AuditRoutes\Output\OutputFactory;

class TestCoverageCommand extends Command
{
    protected $signature = 'route:audit-test-coverage {--export=} {--filename=}';
    protected $description = 'Run security auditing for Laravel routes';

    /**
     * @param Router $router
     * @return void
     */
    public function __construct(private Router $router)
    {
        parent::__construct();
    }

    /** @return void */
    public function handle(): void
    {
        $output = OutputFactory::channel($this->output)->setExporter($this->getExporter())->build();

        $result = AuditRoutes::for($this->router->getRoutes()->getRoutes())
            ->setBenchmark(1)
            ->run([TestAuditor::make()]);

        $output->generate($result);
    }

    /** @return ?ExportInterface */
    protected function getExporter(): ?ExportInterface
    {
        return ExportFactory::channel($this->output)->build(
            $this->option('export'),
            $this->option('filename'),
        )?->setAggregators([
            new FailedPercentage('Uncovered rate'),
            new SuccessPercentage('Covered rate'),
            new AverageScore('Average coverage'),
        ]);
    }
}
