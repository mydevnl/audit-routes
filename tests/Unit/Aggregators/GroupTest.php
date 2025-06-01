<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Aggregators;

use MyDev\AuditRoutes\Aggregators\AverageScore;
use MyDev\AuditRoutes\Aggregators\Group;
use MyDev\AuditRoutes\Aggregators\HighestScore;
use MyDev\AuditRoutes\Aggregators\LowestScore;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Contracts\AggregatorInterface;
use MyDev\AuditRoutes\Tests\Helpers\DummyAuditor;
use MyDev\AuditRoutes\Tests\TestCase;
use MyDev\AuditRoutes\Utilities\Cast;
use PHPUnit\Framework\Attributes\Test;

class GroupTest extends TestCase
{
    #[Test]
    public function it_handles_its_aggregators_for_all_audited_routes(): void
    {
        $routes = ['1', '4', '10'];
        $aggregator = new Group('My group', new LowestScore(), new AverageScore(), new HighestScore());

        AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate($aggregator);

        $expectedResults = [1, 5, 10];
        foreach (Cast::array($aggregator->getResult()) as $index => $result) {
            $this->assertInstanceOf(AggregatorInterface::class, $result);
            $this->assertEquals($expectedResults[$index], $result->getResult());
        }
    }

    #[Test]
    public function it_calls_the_after_method_on_its_aggregators(): void
    {
        $routes = ['2', '4', '10'];
        $mockedAggregator = new class () extends LowestScore {
            public function after(): void
            {
                $this->result *= 999;
            }
        };

        AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate(new Group('My group', $mockedAggregator));

        $this->assertEquals(2 * 999, $mockedAggregator->getResult());
    }

    #[Test]
    public function it_can_be_given_a_name(): void
    {
        $aggregator = new Group('Unique name');

        $this->assertEquals('Unique name', $aggregator->getName());
    }

    #[Test]
    public function it_can_identity_itself(): void
    {
        $aggregator = new Group(null);

        $this->assertEquals('group', $aggregator->getAggregator());
    }

    #[Test]
    public function it_can_return_its_data(): void
    {
        $routes = ['1', '3', '5'];
        $aggregator = new Group(
            'My group',
            new LowestScore('Lowest'),
            new HighestScore('Highest'),
        );

        AuditRoutes::for($routes)
            ->run([DummyAuditor::class])
            ->aggregate($aggregator);

        $this->assertEquals([
            'aggregator' => 'group',
            'name'       => 'My group',
            'result'     => [
                [
                    'aggregator' => 'lowest_score',
                    'name'       => 'Lowest',
                    'result'     => 1,
                ],
                [
                    'aggregator' => 'highest_score',
                    'name'       => 'Highest',
                    'result'     => 5,
                ],
            ],
        ], $aggregator->toArray());
    }
}
