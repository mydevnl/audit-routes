<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    #[Test]
    public function true_is_true(): void
    {
        /** @phpstan-ignore method.alreadyNarrowedType */
        $this->assertTrue(true);
    }
}
