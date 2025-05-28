<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Actions;

use MyDev\AuditRoutes\Attributes\CoversRoute;
use ReflectionClass;
use ReflectionMethod;

class CollectCoversRouteAttribute
{
    /**
     * @param ReflectionClass|ReflectionMethod $reflectionObject
     * @return array<int, string>
     */
    public static function run(ReflectionClass|ReflectionMethod $reflectionObject): array
    {
        $coveredRoutes = [];

        foreach ($reflectionObject->getAttributes(CoversRoute::class) as $classAttribute) {
            array_push($coveredRoutes, ...$classAttribute->getArguments());
        }

        return $coveredRoutes;
    }
}
