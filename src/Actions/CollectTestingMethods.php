<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Actions;

use Illuminate\Support\Facades\Config;
use MyDev\AuditRoutes\Entities\TestingMethod;
use MyDev\AuditRoutes\Utilities\ClassDiscovery;
use ReflectionException;

class CollectTestingMethods
{
    /** @var array<int, TestingMethod> $testingMethods */
    protected static array $testingMethods = [];

    /**
     * @return array<int, TestingMethod>
     * @throws ReflectionException
     */
    public static function run(): array
    {
        if (!empty(self::$testingMethods)) {
            return self::$testingMethods;
        }

        $testClasses = ClassDiscovery::subclassesOf(
            Config::string('audit-routes.tests.implementation'),
            Config::string('audit-routes.tests.directory'),
        );

        foreach ($testClasses as $testClass) {
            array_push(self::$testingMethods, ...CollectTestingMethodsForClass::run($testClass));
        }

        return self::$testingMethods;
    }
}
