<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Aggregators;

use MyDev\AuditRoutes\Aggregators\TotalBetweenScores;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Tests\Helpers\DummyAuditor;
use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TotalBetweenScoresTest extends TestCase
{
    #[Test]
    public function it_counts_the_total_between_given_scores_for_all_audited_routes(): void
    {
        $routes = ['2', '3', '6', '9', '10'];
        $aggregator = new TotalBetweenScores(null, 3, 9);

        AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate($aggregator);

        $this->assertEquals(3, $aggregator->getResult());
    }

    #[Test]
    public function from_can_be_left_empty_to_count_any_value_equal_or_below_the_till(): void
    {
        $routes = ['2', '3', '6', '9', '10'];
        $aggregator = new TotalBetweenScores(null, null, 9);

        AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate($aggregator);

        $this->assertEquals(4, $aggregator->getResult());
    }

    #[Test]
    public function till_can_be_left_empty_to_count_any_value_above_the_from(): void
    {
        $routes = ['2', '3', '6', '9', '10'];
        $aggregator = new TotalBetweenScores(null, 9, null);

        AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate($aggregator);

        $this->assertEquals(2, $aggregator->getResult());
    }

    #[Test]
    public function it_return_zero_when_no_routes_have_been_audited(): void
    {
        $routes = [];
        $aggregator = new TotalBetweenScores(null, null, null);

        AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate($aggregator);

        $this->assertSame(0.0, $aggregator->getResult());
    }

    #[Test]
    public function it_does_not_take_ignored_routes_into_account(): void
    {
        $routes = ['2', '3', '6', '9', '10'];
        $aggregator = new TotalBetweenScores(null, 6, 9);

        AuditRoutes::for($routes)
            ->ignoreRoutes(['6'])
            ->run([DummyAuditor::class])
            ->aggregate($aggregator);

        $this->assertEquals(1, $aggregator->getResult());
    }

    #[Test]
    public function it_can_be_given_a_name(): void
    {
        $aggregator = new TotalBetweenScores('Unique name', null, null);

        $this->assertEquals('Unique name', $aggregator->getName());
    }

    #[Test]
    public function it_can_identity_itself(): void
    {
        $aggregator = new TotalBetweenScores(null, null, null);

        $this->assertEquals('total_between_scores', $aggregator->getAggregator());
    }

    #[Test]
    public function it_can_return_its_data(): void
    {
        $routes = ['2', '3', '6', '9', '10'];
        $aggregator = new TotalBetweenScores('Unique name', null, 5);

        AuditRoutes::for($routes)
            ->setBenchmark(6)
            ->run([DummyAuditor::class])
            ->aggregate($aggregator);

        $this->assertEquals([
            'aggregator' => 'total_between_scores',
            'name'       => 'Unique name',
            'result'     => 2.0,
        ], $aggregator->toArray());
    }
}
