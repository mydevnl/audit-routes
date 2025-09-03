<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Auditors;

use MyDev\AuditRoutes\Auditors\PhpUnitAuditor;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Routes\StringableRoute;
use MyDev\AuditRoutes\Tests\Helpers\MockTestingMethod;
use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PhpUnitAuditorTest extends TestCase
{
    #[Test]
    public function it_increases_the_audited_route_score_for_each_test_found(): void
    {
        $testingMethods = MockTestingMethod::make('
            public function test_it_works_once(): void
            {
                $this->actingAs($user)
                    ->get(route(\'user.index\'))
                    ->assertOk();
            }
            public function test_it_works_twice(): void
            {
                $this->get(route(\'user.index\'))
                    ->assertForbidden();
            }
        ');
        $auditor = (new PhpUnitAuditor())
            ->setTestingMethods($testingMethods);
        $routes = [
            StringableRoute::for('user.index'),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(2, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_detects_routes_used_within_local_variables(): void
    {
        $testingMethods = MockTestingMethod::make('
            public function test_it_works_with_string_values(): void
            {
                $route = \'user.index\';
                $this->get(route($route));
            }
            public function test_it_works_with_function_calls(): void
            {
                $route = route(\'user.index\');
                $this->get($route);
            }
        ');
        $auditor = (new PhpUnitAuditor())
            ->setTestingMethods($testingMethods);
        $routes = [
            StringableRoute::for('user.index'),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(2, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_can_limit_the_maximum_score_given_for_a_route(): void
    {
        $testingMethods = MockTestingMethod::make('
            public function test_it_works_once(): void
            {
                $this->actingAs($user)
                    ->get(route(\'user.index\'))
                    ->assertOk();
            }
            public function test_it_works_twice(): void
            {
                $this->get(route(\'user.index\'))
                    ->assertForbidden();
            }
        ');
        $auditor = (new PhpUnitAuditor())
            ->setTestingMethods($testingMethods)
            ->setLimit(1);
        $routes = [
            StringableRoute::for('user.index'),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(1, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_applies_its_weight_when_increasing_the_audited_route_score(): void
    {
        $testingMethods = MockTestingMethod::make('
            public function test_it_works_once(): void
            {
                $this->actingAs($user)
                    ->get(route(\'user.index\'))
                    ->assertOk();
            }
            public function test_it_works_twice(): void
            {
                $this->get(route(\'user.index\'))
                    ->assertForbidden();
            }
        ');
        $auditor = (new PhpUnitAuditor())
            ->setTestingMethods($testingMethods)
            ->setWeight(50);
        $routes = [
            StringableRoute::for('user.index'),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(100, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_can_penalize_when_tests_are_missing(): void
    {
        $testingMethods = MockTestingMethod::make('
            public function test_this_is_a_different_route(): void
            {
                $this->actingAs($user)
                    ->get(route(\'user.destroy\'))
                    ->assertOk();
            }
        ');
        $auditor = (new PhpUnitAuditor())
            ->setTestingMethods($testingMethods)
            ->setPenalty(-100);
        $routes = [
            StringableRoute::for('user.index'),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(-100, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_can_ignore_routes(): void
    {
        $testingMethods = MockTestingMethod::make('
            public function test_it_works_once(): void
            {
                $this->actingAs($user)
                    ->get(route(\'user.index\'))
                    ->assertOk();
            }
            public function test_this_is_a_different_route(): void
            {
                $this->actingAs($user)
                    ->get(route(\'user.destroy\'))
                    ->assertOk();
            }
        ');
        $auditor = (new PhpUnitAuditor())
            ->setTestingMethods($testingMethods)
            ->ignoreRoutes(['user.destroy']);
        $routes = [
            StringableRoute::for('user.index'),
            StringableRoute::for('user.destroy'),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(1, $auditedRoutes->first()?->getScore());
    }

    #[Test]
    public function it_can_audit_routes_when_specified_conditions_apply(): void
    {
        $testingMethods = MockTestingMethod::make('
            public function test_it_works_once(): void
            {
                $this->actingAs($user)
                    ->get(route(\'user.index\'))
                    ->assertOk();
            }
            public function test_this_is_a_second_route(): void
            {
                $this->actingAs($user)
                    ->get(route(\'user.show\'))
                    ->assertOk();
            }
            public function test_this_is_a_third_route(): void
            {
                $this->actingAs($user)
                    ->get(route(\'user.destroy\'))
                    ->assertOk();
            }
        ');
        $auditor = (new PhpUnitAuditor())
            ->setTestingMethods($testingMethods)
            ->when(fn (RouteInterface $route): bool => str_ends_with($route->getIdentifier(), 'index'));
        $routes = [
            StringableRoute::for('user.index'),
            StringableRoute::for('user.show'),
            StringableRoute::for('user.destroy'),
        ];

        $auditedRoutes = AuditRoutes::for($routes)->run([$auditor]);

        $this->assertEquals(1, $auditedRoutes->first()?->getScore());
    }
}
