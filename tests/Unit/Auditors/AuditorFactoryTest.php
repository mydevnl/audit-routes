<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Auditors;

use InvalidArgumentException;
use MyDev\AuditRoutes\Auditors\AuditorFactory;
use MyDev\AuditRoutes\Auditors\MiddlewareAuditor;
use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class AuditorFactoryTest extends TestCase
{
    #[Test]
    public function it_can_build_an_auditor_by_classname_and_weight(): void
    {
        $auditor = AuditorFactory::build(MiddlewareAuditor::class, 100);

        $this->assertInstanceOf(MiddlewareAuditor::class, $auditor);
        $this->assertEquals(100, $auditor->getWeight());
    }

    #[Test]
    public function it_can_build_an_auditor_by_index_and_classname(): void
    {
        $auditor = AuditorFactory::build(100, MiddlewareAuditor::class);

        $this->assertInstanceOf(MiddlewareAuditor::class, $auditor);
        $this->assertEquals(
            1,
            $auditor->getWeight(),
            'The auditor was expected to have the default weight',
        );
    }

    #[Test]
    public function it_returns_the_same_auditor_instance_when_one_was_provided(): void
    {
        $auditor = new MiddlewareAuditor();
        $returnedAuditor = AuditorFactory::build(100, $auditor);

        $this->assertSame($auditor, $returnedAuditor);
    }

    #[Test]
    #[TestWith([[100, 100], ['UnknownAuditorClass', 100], [100, 'UnknownAuditorClass']])]
    public function it_throws_an_error_when_invalid_arguments_were_provided(array $values): void
    {
        $this->expectException(InvalidArgumentException::class);
        AuditorFactory::build(...$values);
    }

    #[Test]
    public function it_can_build_many_auditors(): void
    {
        $auditors = AuditorFactory::buildMany([
            MiddlewareAuditor::class,
            MiddlewareAuditor::class => 100,
            MiddlewareAuditor::make()->setWeight(50),
        ]);

        $this->assertContainsOnlyInstancesOf(MiddlewareAuditor::class, $auditors);
        $this->assertCount(3, $auditors);
        $expectedWeights = [1, 100, 50];
        foreach ($auditors as $index => $auditor) {
            $this->assertSame($expectedWeights[$index], $auditor->getWeight());
        }
    }
}
