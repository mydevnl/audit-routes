<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Helpers;

use MyDev\AuditRoutes\Actions\CollectTestingMethodsForClass;

class MockTestingMethod
{
    public static function make(string $content): array
    {
        $testCaseSource = self::generateTestCaseSource($content);
        $testCaseClass = eval('?>' . $testCaseSource);

        return CollectTestingMethodsForClass::run($testCaseClass::class, $testCaseSource);
    }

    protected static function generateTestCaseSource(string $content): string
    {
        return <<<PHP
            <?php

            return new class ('generatedMockTest') extends \PHPUnit\Framework\TestCase {
                $content
            };
            PHP;
    }
}
