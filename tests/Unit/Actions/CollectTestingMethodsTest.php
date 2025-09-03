<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Actions;

use Illuminate\Support\Facades\Config;
use MyDev\AuditRoutes\Actions\CollectTestingMethods;
use MyDev\AuditRoutes\Entities\TestingMethod;
use MyDev\AuditRoutes\Tests\Stubs\DummyTestFile;
use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

class CollectTestingMethodsTest extends TestCase
{
    /** @var array<int, TestingMethod> $collectedMethods */
    protected array $collectedMethods;

    public function setUp(): void
    {
        Config::set('audit-routes.tests.implementation', TestCase::class);

        $dummyFile = (new ReflectionClass(DummyTestFile::class))->getFileName();

        $this->collectedMethods = CollectTestingMethods::run(dirname((string) $dummyFile));
    }

    #[Test]
    public function it_collects_testing_methods_for_a_directory(): void
    {
        $this->assertNotEmpty($this->collectedMethods);
        $this->assertContainsOnlyInstancesOf(TestingMethod::class, $this->collectedMethods);
        foreach ($this->collectedMethods as $method) {
            $this->assertTrue($method->isValid());
        }
    }

    #[Test]
    public function it_does_not_collect_methods_which_are_not_public(): void
    {
        foreach ($this->collectedMethods as $method) {
            $this->assertNotEquals('setValue', $method->getName());
        }
    }

    #[Test]
    public function it_does_not_collect_methods_which_are_returning_a_value(): void
    {
        foreach ($this->collectedMethods as $method) {
            $this->assertNotEquals('getValue', $method->getName());
        }
    }

    #[Test]
    public function it_does_not_collect_methods_which_are_static(): void
    {
        foreach ($this->collectedMethods as $method) {
            $this->assertNotEquals('staticVoid', $method->getName());
        }
    }
}
