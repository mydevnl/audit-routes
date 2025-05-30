<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Examples\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use MyDev\AuditRoutes\Aggregators\AverageScore;
use MyDev\AuditRoutes\Aggregators\ConditionedCumulative;
use MyDev\AuditRoutes\Aggregators\FailedPercentage;
use MyDev\AuditRoutes\Aggregators\SuccessPercentage;
use MyDev\AuditRoutes\Auditors\ScopedBindingAuditor;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Contracts\ExportInterface;
use MyDev\AuditRoutes\Output\Export\ExportFactory;
use MyDev\AuditRoutes\Output\OutputFactory;

class ScopedBindingCommand extends Command
{
    protected $signature = 'route:audit-scoped-bindings {--export=} {--filename=}';
    protected $description = 'Scoped bindings auditing for Laravel routes';

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
