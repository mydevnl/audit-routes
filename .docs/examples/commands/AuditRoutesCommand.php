<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Examples\Commands;

use MyDev\AuditRoutes\Auditors\MiddlewareAuditor;
use MyDev\AuditRoutes\Auditors\PermissionAuditor;
use MyDev\AuditRoutes\Auditors\PolicyAuditor;
use MyDev\AuditRoutes\Auditors\TestAuditor;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Output\ConsoleFactory;
use MyDev\AuditRoutes\Repositories\RouteInterface;

class AuditRoutesCommand extends Command
{
    protected $signature = 'route:audit';
    protected $description = 'Run security auditing for Laravel routes';

    public function __construct(private Router $router)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $output = ConsoleFactory::build($this->getOutput()->getVerbosity(), $this->output);

        $result = AuditRoutes::for($this->router->getRoutes()->getRoutes())
            ->setBenchmark(1000)
            ->run([
                PolicyAuditor::class => 100,
                PermissionAuditor::class => -100,
                TestAuditor::make()->setWeight(250)->setPenalty(-10000)->setLimit(2333),
                MiddlewareAuditor::make(['auth'])
                    ->when(fn (RouteInterface $route): bool => !str_starts_with($route->getName(), 'api'))
                    ->ignoreRoutes(['login', 'password.update', 'password.request', 'password.reset', 'password-link.store'])
                    ->setPenalty(-1000)
                    ->setWeight(10),
                MiddlewareAuditor::make(['auth:sanctum'])
                    ->when(fn (RouteInterface $route): bool => str_starts_with($route->getName(), 'api'))
                    ->ignoreRoutes(['api.password', 'api.login', 'api.register'])
                    ->setPenalty(-1000)
                    ->setWeight(10),
            ]);

        $output->generate($result);
    }
}
