<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Examples\Commands;

use MyDev\AuditRoutes\Auditors\MiddlewareAuditor;
use MyDev\AuditRoutes\Auditors\PermissionAuditor;
use MyDev\AuditRoutes\Auditors\PolicyAuditor;
use MyDev\AuditRoutes\Auditors\TestAuditor;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use MyDev\AuditRoutes\Aggregators\AverageScore;
use MyDev\AuditRoutes\Aggregators\FailedPercentage;
use MyDev\AuditRoutes\Aggregators\HighestScore;
use MyDev\AuditRoutes\Aggregators\LowestScore;
use MyDev\AuditRoutes\Aggregators\MedianScore;
use MyDev\AuditRoutes\Aggregators\ModeScore;
use MyDev\AuditRoutes\Aggregators\SuccessPercentage;
use MyDev\AuditRoutes\Aggregators\TotalAroundBenchmark;
use MyDev\AuditRoutes\Aggregators\TotalBetweenScores;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Output\OutputFactory;
use MyDev\AuditRoutes\Routes\RouteInterface;

class AuditRoutesCommand extends Command
{
    protected $signature = 'route:audit {--export}';
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
        $output = OutputFactory::channel($this->output)
            ->withExporter($this->option('export'), [
                new LowestScore(),
                new HighestScore(),
                new AverageScore(),
                new MedianScore(),
                new ModeScore(),
                new FailedPercentage(),
                new SuccessPercentage(),
                new TotalBetweenScores(-1000, 0),
                new TotalAroundBenchmark(20, 20),
            ])->build();

        $result = AuditRoutes::for($this->router->getRoutes()->getRoutes())
            ->setBenchmark(1000)
            ->run([
                PolicyAuditor::class => 100,
                PermissionAuditor::class => -100,
                TestAuditor::make()->setWeight(250)->setPenalty(-10000)->setLimit(2333),
                MiddlewareAuditor::make(['auth'])
                    ->ignoreRoutes(['login', 'password*', 'api.*'])
                    ->setPenalty(-1000)
                    ->setWeight(10),
                MiddlewareAuditor::make(['auth:sanctum'])
                    ->when(fn (RouteInterface $route): bool => str_starts_with($route->getIdentifier(), 'api'))
                    ->ignoreRoutes(['api.password', 'api.login', 'api.register'])
                    ->setPenalty(-1000)
                    ->setWeight(10),
            ]);

        $output->generate($result);
    }
}
