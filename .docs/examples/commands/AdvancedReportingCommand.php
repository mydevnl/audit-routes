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
use MyDev\AuditRoutes\Auditors\TestAuditor;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Output\ExportFactory;
use MyDev\AuditRoutes\Output\ExportInterface;
use MyDev\AuditRoutes\Output\OutputFactory;
use MyDev\AuditRoutes\Routes\RouteInterface;

class AdvancedReportingCommand extends Command
{
    protected $signature = 'route:audit {--benchmark=1000} {--export=} {--filename=}';
    protected $description = 'Run Advanced Reporting auditing for Laravel routes';

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
            ->run([
                PolicyAuditor::class => 100,
                PermissionAuditor::class => -100,
                TestAuditor::make()->setWeight(250)->setPenalty(-10000)->setLimit(2333),
                MiddlewareAuditor::make(['auth'])
                    ->ignoreRoutes(['login', 'password*', 'api.*'])
                    ->setPenalty(-1000)
                    ->setWeight(10)
                    ->setName('MiddlewareAuditor auth'),
                MiddlewareAuditor::make(['auth:sanctum'])
                    ->when(fn(RouteInterface $route): bool => str_starts_with($route->getIdentifier(), 'api'))
                    ->ignoreRoutes(['api.password', 'api.login', 'api.register'])
                    ->setPenalty(-1000)
                    ->setWeight(10)
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
            new FailedPercentage('Failed rate'),
            new SuccessPercentage('Success rate'),
            new LowestScore('Lowest'),
            new HighestScore('Highest'),
            new AverageScore('Average'),
            new MedianScore('Median'),
            new ModeScore('Mode'),
            new Group(
                'Total scores',
                new TotalBetweenScores('Between -15,000 and -5,000', -15_000, -5_000),
                new TotalBetweenScores('Between -5,000 and 0', -5_000, 0),
                new TotalBetweenScores('Between 0 and 1,000', 0, 1000),
                new TotalBetweenScores('Between 1,000 and 2,000', 1000, 2000),
                new TotalBetweenScores('Between 2,000 and 3,000', 2000, 3000),
            ),
            new Group(
                'Relative scores to benchmark',
                new TotalAroundBenchmark('Below 80%', null, 0.8),
                new TotalAroundBenchmark('Between 80% and 100%', 0.8, 1),
                new TotalAroundBenchmark('Between 100% and 120%', 1, 1.2),
                new TotalAroundBenchmark('Over 120%', 1.2, null),
            ),
        ]);
    }
}
