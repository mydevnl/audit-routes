<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Actions;

use MyDev\AuditRoutes\Actions\CollectTestingMethodsForClass;
use MyDev\AuditRoutes\Entities\NodeAccessor;
use MyDev\AuditRoutes\Entities\TestingMethod;
use MyDev\AuditRoutes\Tests\Stubs\DummyTestFile;
use MyDev\AuditRoutes\Tests\TestCase;
use PhpParser\Node\Identifier;
use PHPUnit\Framework\Attributes\Test;

class CollectTestingMethodsForClassTest extends TestCase
{
    #[Test]
    public function it_collects_testing_methods_for_a_class(): void
    {
        $collectedMethods = CollectTestingMethodsForClass::run(DummyTestFile::class);

        $this->assertNotEmpty($collectedMethods);
        $this->assertContainsOnlyInstancesOf(TestingMethod::class, $collectedMethods);
        foreach ($collectedMethods as $method) {
            $this->assertTrue($method->isValid());
        }
    }

    #[Test]
    public function it_return_an_empty_array_when_an_invalid_class_was_provided(): void
    {
        /** @phpstan-ignore-next-line */
        $collectedMethods = CollectTestingMethodsForClass::run('ThisClassDoesNotExist');

        $this->assertEquals([], $collectedMethods);
    }

    #[Test]
    public function it_appends_the_node_accessor_to_testing_methods(): void
    {
        [$collectedMethod] = CollectTestingMethodsForClass::run(DummyTestFile::class);

        $this->assertInstanceOf(NodeAccessor::class, $collectedMethod->getNodeAccessor());
        $this->assertTrue($collectedMethod->getNodeAccessor()->has(function (Identifier $identifier): bool {
            return $identifier->name === 'dummy_test_method';
        }));
    }

    #[Test]
    public function it_does_not_collect_methods_which_are_not_public(): void
    {
        $collectedMethods = CollectTestingMethodsForClass::run(DummyTestFile::class);

        foreach ($collectedMethods as $method) {
            $this->assertNotEquals('setValue', $method->getName());
        }
    }

    #[Test]
    public function it_does_not_collect_methods_which_are_returning_a_value(): void
    {
        $collectedMethods = CollectTestingMethodsForClass::run(DummyTestFile::class);

        foreach ($collectedMethods as $method) {
            $this->assertNotEquals('getValue', $method->getName());
        }
    }

    #[Test]
    public function it_does_not_collect_methods_which_are_static(): void
    {
        $collectedMethods = CollectTestingMethodsForClass::run(DummyTestFile::class);

        foreach ($collectedMethods as $method) {
            $this->assertNotEquals('staticVoid', $method->getName());
        }
    }
}
