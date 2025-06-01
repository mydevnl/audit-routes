<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Aggregators;

use MyDev\AuditRoutes\Aggregators\MedianScore;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Tests\Helpers\DummyAuditor;
use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MedianScoreTest extends TestCase
{
    #[Test]
    public function it_calculates_the_median_score_for_all_audited_routes(): void
    {
        $routes = ['1', '2', '10', '80', '100'];

        [$aggregator] = AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new MedianScore());

        $this->assertEquals(10, $aggregator->getResult());
    }

    #[Test]
    public function for_even_counts_it_uses_the_average_between_the_two_center_values(): void
    {
        $routes = ['1', '30', '40', '100'];

        [$aggregator] = AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new MedianScore());

        $this->assertEquals(35, $aggregator->getResult());
    }

    #[Test]
    public function it_return_zero_when_no_routes_have_been_audited(): void
    {
        $routes = [];

        [$aggregator] = AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new MedianScore());

        $this->assertSame(0.0, $aggregator->getResult());
    }

    #[Test]
    public function it_does_not_take_ignored_routes_into_account(): void
    {
        $routes = ['1', '2', '2', '3', '4'];

        [$aggregator] = AuditRoutes::for($routes)
            ->ignoreRoutes(['2'])
            ->run([DummyAuditor::class])
            ->aggregate(new MedianScore());

        $this->assertEquals(3, $aggregator->getResult());
    }

    #[Test]
    public function it_can_be_given_a_name(): void
    {
        $aggregator = new MedianScore('Unique name');

        $this->assertEquals('Unique name', $aggregator->getName());
    }

    #[Test]
    public function it_can_identity_itself(): void
    {
        $aggregator = new MedianScore();

        $this->assertEquals('median_score', $aggregator->getAggregator());
    }

    #[Test]
    public function it_can_return_its_data(): void
    {
        $routes = ['2', '3', '10'];

        [$aggregator] = AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new MedianScore('Unique name'));

        $this->assertEquals([
            'aggregator' => 'median_score',
            'name'       => 'Unique name',
            'result'     => 3,
        ], $aggregator->toArray());
    }
}
