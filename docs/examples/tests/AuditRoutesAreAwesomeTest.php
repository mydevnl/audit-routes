<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Examples\Tests;

use MyDev\AuditRoutes\Auditors\PermissionAuditor;
use MyDev\AuditRoutes\Auditors\PolicyAuditor;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Entities\AuditedRouteCollection;
use MyDev\AuditRoutes\Testing\Concerns\AssertsAuditRoutes;
use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuditRoutesAreAwesomeTest extends TestCase
{
    use AssertsAuditRoutes;

    #[Test]
    public function all_routes_are_tested(): void
    {
        $this->assertRoutesAreTested(['*']);
    }

    #[Test]
    public function some_routes_are_tested(): void
    {
        $this->assertRoutesAreTested([
            'welcome',
            'login',
            'logout',
        ]);
    }

    #[Test]
    public function a_specific_route_is_tested(): void
    {
        $this->assertRouteIsTested('welcome');
    }

    #[Test]
    public function all_routes_have_auth_middleware(): void
    {
        $this->assertRoutesHaveMiddleware(['*'], ['auth']);
    }

    #[Test]
    public function all_api_routes_have_auth_sanctum_middleware(): void
    {
        $this->assertRoutesHaveMiddleware(
            ['*'],
            ['auth:sanctum'],
            ignoredRoutes: ['api.login'],
            when: fn (RouteInterface $route): bool => str_starts_with($route->getIdentifier(), 'api'),
        );
    }

    #[Test]
    public function a_specific_route_has_auth_sanctum_middleware(): void
    {
        $this->assertRouteHasMiddleware('api.welcome', ['auth:sanctum']);
    }

    #[Test]
    public function no_routes_are_protected_by_permissions(): void
    {
        $message = function (AuditedRouteCollection $failedRoutes): string {
            $lines = ['The following routes are protected by permissions:', ...$failedRoutes->get()];

            return implode("\n\t", $lines);
        };

        $this->assertAuditRoutesOk(['*'], [PermissionAuditor::make()->setWeight(-1)], $message);
    }

    #[Test]
    public function some_routes_are_protected_by_policies(): void
    {
        $routes = [
            'user.show',
            'user.index',
            'user.create',
            'user.store',
            'user.edit',
            'user.store',
            'user.delete',
            'user.destroy',
        ];
        $message = 'Not all of the expected routes are protected by policies';

        $this->assertAuditRoutesOk($routes, [PolicyAuditor::make()], $message, benchmark: 1);
    }
}
