<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Aggregators;

use MyDev\AuditRoutes\Aggregators\HighestScore;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Tests\Helpers\DummyAuditor;
use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HighestScoreTest extends TestCase
{
    #[Test]
    public function it_calculates_the_highest_score_for_all_audited_routes(): void
    {
        $routes = ['2', '10', '3'];

        [$aggregator] = AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new HighestScore());

        $this->assertEquals(10, $aggregator->getResult());
    }

    #[Test]
    public function it_return_zero_when_no_routes_have_been_audited(): void
    {
        $routes = [];

        [$aggregator] = AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new HighestScore());

        $this->assertSame(0.0, $aggregator->getResult());
    }

    #[Test]
    public function it_does_not_take_ignored_routes_into_account(): void
    {
        $routes = ['2', '10', '3', '999'];

        [$aggregator] = AuditRoutes::for($routes)
            ->ignoreRoutes(['999'])
            ->run([DummyAuditor::class])
            ->aggregate(new HighestScore());

        $this->assertEquals(10, $aggregator->getResult());
    }

    #[Test]
    public function it_can_be_given_a_name(): void
    {
        $aggregator = new HighestScore('Unique name');

        $this->assertEquals('Unique name', $aggregator->getName());
    }

    #[Test]
    public function it_can_identity_itself(): void
    {
        $aggregator = new HighestScore();

        $this->assertEquals('highest_score', $aggregator->getAggregator());
    }

    #[Test]
    public function it_can_return_its_data(): void
    {
        $routes = ['2', '3', '10'];

        [$aggregator] = AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new HighestScore('Unique name'));

        $this->assertEquals([
            'aggregator' => 'highest_score',
            'name'       => 'Unique name',
            'result'     => 10,
        ], $aggregator->toArray());
    }
}
