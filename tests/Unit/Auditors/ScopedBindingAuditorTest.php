<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Auditors;

use MyDev\AuditRoutes\Auditors\ScopedBindingAuditor;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Entities\Middleware;
use MyDev\AuditRoutes\Routes\StringableRoute;
use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ScopedBindingAuditorTest extends TestCase
{
    #[Test]
    public function it_increases_the_audited_route_score_twice_when_scoped_binding_was_applied(): void
    {
        $auditor = ScopedBindingAuditor::make();
        $routes = [
            StringableRoute::for('/team/{team}/user/{user}')->setScopedBindings(true),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(2, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_increases_the_audited_route_score_when_scoped_bindings_are_neither_applied_nor_required(): void
    {
        $auditor = ScopedBindingAuditor::make();
        $routes = [
            StringableRoute::for('/user/{user}')->setScopedBindings(false),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(1, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_can_penalize_when_scoped_bindings_are_required_and_missing(): void
    {
        $auditor = ScopedBindingAuditor::make()->setPenalty(-500);
        $routes = [
            StringableRoute::for('/team/{team}/user/{user}')->setScopedBindings(false),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(-500, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_can_limit_the_maximum_score_given_for_a_route(): void
    {
        $auditor = ScopedBindingAuditor::make()->setLimit(1);
        $routes = [
            StringableRoute::for('/team/{team}/user/{user}')->setScopedBindings(true),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(1, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_applies_its_weight_when_increasing_the_audited_route_score(): void
    {
        $auditor = ScopedBindingAuditor::make()->setWeight(500);
        $routes = [
            StringableRoute::for('/team/{team}/user/{user}')->setScopedBindings(true),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(2 * 500, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_increases_the_audited_route_score_once_when_scoped_bindings_are_not_supported(): void
    {
        $auditor = ScopedBindingAuditor::make();
        $routes = [
            StringableRoute::for('/user/{user}')->setScopedBindings(null),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(1, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_can_ignore_routes(): void
    {
        $auditor = ScopedBindingAuditor::make()
            ->ignoreRoutes(['/api/*'])
            ->setPenalty(-500);
        $routes = [
            StringableRoute::for('/team/{team}/user/{user}')->setScopedBindings(true),
            StringableRoute::for('/api/team/{team}/user/{user}')->setScopedBindings(false),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertCount(2, $auditedRoutes);
        $expectedScores = [
            '/team/{team}/user/{user}'     => 2,
            '/api/team/{team}/user/{user}' => 0,
        ];
        foreach ($auditedRoutes as $auditedRoute) {
            $this->assertEquals($expectedScores[$auditedRoute->getDisplayName()], $auditedRoute->getScore());
        }
    }

    #[Test]
    public function it_can_audit_routes_when_specified_conditions_apply(): void
    {
        $auditor = ScopedBindingAuditor::make(['auth:sanctum'])
            ->when(fn (RouteInterface $route): bool => $route->hasMiddleware('api'))
            ->setPenalty(-500);
        $routes = [
            StringableRoute::for('/team/{team}/user/{user}')
                ->setMiddlewares([Middleware::from('auth')])
                ->setScopedBindings(false),
            StringableRoute::for('/api/team/{team}/user/{user}')
                ->setMiddlewares([Middleware::from('api')])
                ->setScopedBindings(false),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertCount(2, $auditedRoutes);
        $expectedScores = [
            '/team/{team}/user/{user}'     => 0,
            '/api/team/{team}/user/{user}' => -500,
        ];
        foreach ($auditedRoutes as $auditedRoute) {
            $this->assertEquals($expectedScores[$auditedRoute->getDisplayName()], $auditedRoute->getScore());
        }
    }
}
