<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Actions;

use Illuminate\Support\Facades\Config;
use MyDev\AuditRoutes\Entities\TestingMethod;
use MyDev\AuditRoutes\Utilities\Cast;
use MyDev\AuditRoutes\Utilities\ClassDiscovery;

class CollectTestingMethods
{
    /** @var array<string, array<int, TestingMethod>> $testingMethods */
    protected static array $testingMethods = [];

    /** @return array<int, TestingMethod> */
    public static function run(string $directory): array
    {
        if (isset(self::$testingMethods[$directory])) {
            return self::$testingMethods[$directory];
        }

        $testImplementation = Cast::string(Config::get('audit-routes.tests.implementation'));
        $testClasses = ClassDiscovery::subclassesOf($testImplementation, $directory);

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
