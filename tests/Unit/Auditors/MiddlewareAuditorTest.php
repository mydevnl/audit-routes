<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Auditors;

use MyDev\AuditRoutes\Auditors\MiddlewareAuditor;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Entities\Middleware;
use MyDev\AuditRoutes\Routes\StringableRoute;
use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MiddlewareAuditorTest extends TestCase
{
    #[Test]
    public function it_increases_the_audited_route_score_for_each_middleware_found(): void
    {
        $auditor = MiddlewareAuditor::make(['auth', 'password-change', 'two-factor']);
        $routes = [
            StringableRoute::for('user.index')->setMiddlewares([
                Middleware::from('auth'),
                Middleware::from('password-change'),
            ]),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(2, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_can_limit_the_maximum_score_given_for_a_route(): void
    {
        $auditor = MiddlewareAuditor::make(['auth', 'password-change', 'two-factor'])->setLimit(1);
        $routes = [
            StringableRoute::for('user.index')->setMiddlewares([
                Middleware::from('auth'),
                Middleware::from('password-change'),
                Middleware::from('two-factor'),
            ]),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(1, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_applies_its_weight_when_increasing_the_audited_route_score(): void
    {
        $auditor = MiddlewareAuditor::make(['auth', 'password-change', 'two-factor'])->setWeight(500);
        $routes = [
            StringableRoute::for('user.index')->setMiddlewares([
                Middleware::from('auth'),
                Middleware::from('password-change'),
            ]),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(2 * 500, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_can_penalize_when_all_middlewares_are_missing(): void
    {
        $auditor = MiddlewareAuditor::make(['auth:sanctum', 'password-change', 'two-factor'])->setPenalty(-500);
        $routes = [
            StringableRoute::for('api.user.index')->setMiddlewares([
                Middleware::from('auth'),
            ]),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(-500, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_can_ignore_routes(): void
    {
        $auditor = MiddlewareAuditor::make(['auth', 'password-change', 'two-factor'])
            ->ignoreRoutes(['api.*'])
            ->setPenalty(-500);
        $routes = [
            StringableRoute::for('user.index')->setMiddlewares([
                Middleware::from('auth'),
                Middleware::from('password-change'),
            ]),
            StringableRoute::for('api.user.index')->setMiddlewares([
                Middleware::from('auth:sanctum'),
            ]),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertCount(2, $auditedRoutes);
        $expectedScores = [
            'user.index'     => 2,
            'api.user.index' => 0,
        ];
        foreach ($auditedRoutes as $auditedRoute) {
            $this->assertEquals($expectedScores[$auditedRoute->getDisplayName()], $auditedRoute->getScore());
        }
    }

    #[Test]
    public function it_can_audit_routes_when_specified_conditions_apply(): void
    {
        $auditor = MiddlewareAuditor::make(['auth:sanctum'])
            ->when(fn (RouteInterface $route): bool => $route->hasMiddleware('api'))
            ->setPenalty(-500);
        $routes = [
            StringableRoute::for('user.index')->setMiddlewares([
                Middleware::from('web'),
                Middleware::from('auth'),
                Middleware::from('password-change'),
            ]),
            StringableRoute::for('api.user.index')->setMiddlewares([
                Middleware::from('api'),
                Middleware::from('auth'),
            ]),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertCount(2, $auditedRoutes);
        $expectedScores = [
            'user.index'     => 0,
            'api.user.index' => -500,
        ];
        foreach ($auditedRoutes as $auditedRoute) {
            $this->assertEquals($expectedScores[$auditedRoute->getDisplayName()], $auditedRoute->getScore());
        }
    }
}
