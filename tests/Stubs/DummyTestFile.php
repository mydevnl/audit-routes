<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Stubs;

use MyDev\AuditRoutes\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class DummyTestFile extends TestCase
{
    protected bool $value = false;

    #[DataProvider('dataProvider')]
    #[Test]
    public function dummy_test_method(bool $value, bool $equalsTrue): void
    {
        $this->setValue($value);

        $this->assertSame($equalsTrue, $this->getValue());
    }

    public function getValue(): bool
    {
        return $this->value;
    }

    public static function dataProvider(): array
    {
        return [
            [true, true],
            [false, false],
        ];
    }

    public static function staticVoid(): void
    {
    }

    protected function setValue(bool $value): void
    {
        $this->value = $value;
    }
}
