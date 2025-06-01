<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Aggregators;

use MyDev\AuditRoutes\Aggregators\SuccessPercentage;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Tests\Helpers\DummyAuditor;
use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SuccessPercentageTest extends TestCase
{
    #[Test]
    public function it_calculates_the_success_percentage_for_all_audited_routes(): void
    {
        $routes = ['1', '2', '3'];

        [$aggregator] = AuditRoutes::for($routes)
            ->setBenchmark(2)
            ->run([DummyAuditor::class])
            ->aggregate(new SuccessPercentage());

        $this->assertEquals(66.67, $aggregator->getResult());
    }

    #[Test]
    public function it_return_hundred_percentage_when_no_routes_have_been_audited(): void
    {
        $routes = [];

        [$aggregator] = AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new SuccessPercentage());

        $this->assertEquals(100, $aggregator->getResult());
    }

    #[Test]
    public function it_does_not_take_ignored_routes_into_account(): void
    {
        $routes = ['1', '2', '3', '4', '999'];

        [$aggregator] = AuditRoutes::for($routes)
            ->ignoreRoutes(['999'])
            ->setBenchmark(2)
            ->run([DummyAuditor::class])
            ->aggregate(new SuccessPercentage());

        $this->assertEquals(75, $aggregator->getResult());
    }

    #[Test]
    public function it_can_be_given_a_name(): void
    {
        $aggregator = new SuccessPercentage('Unique name');

        $this->assertEquals('Unique name', $aggregator->getName());
    }

    #[Test]
    public function it_can_identity_itself(): void
    {
        $aggregator = new SuccessPercentage();

        $this->assertEquals('success_percentage', $aggregator->getAggregator());
    }

    #[Test]
    public function it_can_return_its_data(): void
    {
        $routes = ['1', '2', '3', '4', '5'];

        [$aggregator] = AuditRoutes::for($routes)
            ->setBenchmark(2)
            ->run([DummyAuditor::class])
            ->aggregate(new SuccessPercentage('Unique name'));

        $this->assertEquals([
            'aggregator' => 'success_percentage',
            'name'       => 'Unique name',
            'result'     => 80,
        ], $aggregator->toArray());
    }
}
