<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Aggregators;

use MyDev\AuditRoutes\Aggregators\ModeScore;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Tests\Helpers\DummyAuditor;
use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ModeScoreTest extends TestCase
{
    #[Test]
    public function it_calculates_the_mode_score_for_all_audited_routes(): void
    {
        $routes = ['1', '10', '10', '2', '3', '3', '10'];

        [$aggregator] = AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new ModeScore());

        $this->assertEquals(10, $aggregator->getResult());
    }

    #[Test]
    public function for_multiple_modes_it_falls_back_to_the_lowest_value_of_the_modes(): void
    {
        $routes = ['50', '50', '99', '5', '5', '99'];

        [$aggregator] = AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new ModeScore());

        $this->assertEquals(5, $aggregator->getResult());
    }

    #[Test]
    public function it_return_zero_when_no_routes_have_been_audited(): void
    {
        $routes = [];

        [$aggregator] = AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new ModeScore());

        $this->assertSame(0.0, $aggregator->getResult());
    }

    #[Test]
    public function it_does_not_take_ignored_routes_into_account(): void
    {
        $routes = ['1', '2', '10', '10', '10', '3', '3'];

        [$aggregator] = AuditRoutes::for($routes)
            ->ignoreRoutes(['10'])
            ->run([DummyAuditor::class])
            ->aggregate(new ModeScore());

        $this->assertEquals(3, $aggregator->getResult());
    }

    #[Test]
    public function it_can_be_given_a_name(): void
    {
        $aggregator = new ModeScore('Unique name');

        $this->assertEquals('Unique name', $aggregator->getName());
    }

    #[Test]
    public function it_can_identity_itself(): void
    {
        $aggregator = new ModeScore();

        $this->assertEquals('mode_score', $aggregator->getAggregator());
    }

    #[Test]
    public function it_can_return_its_data(): void
    {
        $routes = ['2', '3', '3', '10'];

        [$aggregator] = AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new ModeScore('Unique name'));

        $this->assertEquals([
            'aggregator' => 'mode_score',
            'name'       => 'Unique name',
            'result'     => 3,
        ], $aggregator->toArray());
    }
}
