<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Auditors;

use MyDev\AuditRoutes\Auditors\PolicyAuditor;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Entities\Middleware;
use MyDev\AuditRoutes\Routes\StringableRoute;
use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class PolicyAuditorTest extends TestCase
{
    #[Test]
    public function it_can_increase_the_audited_route_score_for_each_present_permission(): void
    {
        $auditor = PolicyAuditor::make();
        $routes = [
            StringableRoute::for('user.edit')->setMiddlewares([
                Middleware::from('can:view,user'),
                Middleware::from('can:edit,user'),
            ]),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(2, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    #[TestWith(['auth', 'role:admin', 'can:update-users'])]
    public function it_can_distinct_other_middleware_from_policies(string $middleware): void
    {
        $auditor = PolicyAuditor::make();
        $routes = [
            StringableRoute::for('user.edit')->setMiddlewares([Middleware::from($middleware)]),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(0, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_can_limit_the_maximum_score_given_for_a_route(): void
    {
        $auditor = PolicyAuditor::make([])->setLimit(1);
        $routes = [
            StringableRoute::for('user.edit')->setMiddlewares([
                Middleware::from('can:view,user'),
                Middleware::from('can:edit,user'),
            ]),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(1, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_applies_its_weight_when_increasing_the_audited_route_score(): void
    {
        $auditor = PolicyAuditor::make()->setWeight(100);
        $routes = [
            StringableRoute::for('user.edit')->setMiddlewares([
                Middleware::from('can:view,user'),
            ]),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(100, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_can_penalize_when_no_policies_are_present(): void
    {
        $auditor = PolicyAuditor::make([])->setPenalty(-100);
        $routes = [
            StringableRoute::for('api.user.edit')->setMiddlewares([]),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(-100, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_can_ignore_routes(): void
    {
        $auditor = PolicyAuditor::make([])
            ->ignoreRoutes(['api.*'])
            ->setPenalty(-500);
        $routes = [
            StringableRoute::for('user.edit')->setMiddlewares([
                Middleware::from('can:view,user'),
                Middleware::from('can:edit,user'),
            ]),
            StringableRoute::for('api.user.edit')->setMiddlewares([]),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertCount(2, $auditedRoutes);
        $expectedScores = [
            'user.edit'     => 2,
            'api.user.edit' => 0,
        ];
        foreach ($auditedRoutes as $auditedRoute) {
            $this->assertEquals($expectedScores[$auditedRoute->getDisplayName()], $auditedRoute->getScore());
        }
    }

    #[Test]
    public function it_can_audit_routes_when_specified_conditions_apply(): void
    {
        $auditor = PolicyAuditor::make([])
            ->when(fn (RouteInterface $route): bool => $route->hasMiddleware('api'))
            ->setWeight(500);
        $routes = [
            StringableRoute::for('user.edit')->setMiddlewares([
                Middleware::from('can:view,user'),
                Middleware::from('can:edit,user'),
            ]),
            StringableRoute::for('api.user.edit')->setMiddlewares([
                Middleware::from('api'),
                Middleware::from('can:view,user'),
            ]),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertCount(2, $auditedRoutes);
        $expectedScores = [
            'user.edit'     => 0,
            'api.user.edit' => 500,
        ];
        foreach ($auditedRoutes as $auditedRoute) {
            $this->assertEquals($expectedScores[$auditedRoute->getDisplayName()], $auditedRoute->getScore());
        }
    }
}
