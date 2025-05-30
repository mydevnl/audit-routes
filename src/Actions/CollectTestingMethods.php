<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Actions;

use Illuminate\Support\Facades\Config;
use MyDev\AuditRoutes\Entities\TestingMethod;
use MyDev\AuditRoutes\Utilities\ClassDiscovery;
use ReflectionException;

class CollectTestingMethods
{
    /** @var array<string, array<int, TestingMethod>> $testingMethods */
    protected static array $testingMethods = [];

    /**
     * @return array<int, TestingMethod>
     *
     * @throws ReflectionException
     */
    public static function run(string $directory): array
    {
        if (isset(self::$testingMethods[$directory])) {
            return self::$testingMethods[$directory];
        }

        $testClasses = ClassDiscovery::subclassesOf(
            Config::string('audit-routes.tests.implementation'),
            $directory,
        );

        self::$testingMethods[$directory] = [];

        foreach ($testClasses as $testClass) {
            array_push(
                self::$testingMethods[$directory],
                ...CollectTestingMethodsForClass::run($testClass),
            );
        }

        return self::$testingMethods[$directory];
    }
}
