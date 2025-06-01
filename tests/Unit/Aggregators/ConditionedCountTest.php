<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Aggregators;

use MyDev\AuditRoutes\Aggregators\ConditionedCount;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Entities\AuditedRoute;
use MyDev\AuditRoutes\Tests\Helpers\DummyAuditor;
use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ConditionedCountTest extends TestCase
{
    #[Test]
    public function it_can_count_conditioned_audited_routes(): void
    {
        $routes = ['2', '4', '6', '8'];

        [$aggregator] = AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new ConditionedCount(condition: function (AuditedRoute $auditedRoute): bool {
                return $auditedRoute->getScore() > 5;
            }));

        $this->assertEquals(2, $aggregator->getResult());
    }

    #[Test]
    public function it_can_count_all_audited_routes(): void
    {
        $routes = ['2', '4', '6', '8'];

        [$aggregator] = AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new ConditionedCount());

        $this->assertEquals(4, $aggregator->getResult());
    }

    #[Test]
    public function it_return_zero_when_no_routes_have_been_audited(): void
    {
        $routes = [];

        [$aggregator] = AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new ConditionedCount());

        $this->assertSame(0.0, $aggregator->getResult());
    }

    #[Test]
    public function it_does_not_take_ignored_routes_into_account(): void
    {
        $routes = ['2', '4', '6', '8', '999'];

        [$aggregator] = AuditRoutes::for($routes)
            ->ignoreRoutes(['999'])
            ->run([DummyAuditor::class])
            ->aggregate(new ConditionedCount(condition: function (AuditedRoute $auditedRoute): bool {
                return $auditedRoute->getScore() > 5;
            }));

        $this->assertEquals(2, $aggregator->getResult());
    }

    #[Test]
    public function it_can_be_given_a_name(): void
    {
        $aggregator = new ConditionedCount('Unique name');

        $this->assertEquals('Unique name', $aggregator->getName());
    }

    #[Test]
    public function it_can_identity_itself(): void
    {
        $aggregator = new ConditionedCount();

        $this->assertEquals('conditioned_count', $aggregator->getAggregator());
    }

    #[Test]
    public function it_can_return_its_data(): void
    {
        $routes = ['2', '4', '6', '8'];

        [$aggregator] = AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new ConditionedCount('Unique name', function (AuditedRoute $auditedRoute): bool {
                return $auditedRoute->getScore() > 5;
            }));

        $this->assertEquals([
            'aggregator' => 'conditioned_count',
            'name'       => 'Unique name',
            'result'     => 2,
        ], $aggregator->toArray());
    }
}
