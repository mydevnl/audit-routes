<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Actions;

use MyDev\AuditRoutes\Entities\TestingMethod;
use MyDev\AuditRoutes\Utilities\ClassDiscovery;
use MyDev\AuditRoutes\Visitors\FindMethodNodeByNameVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class CollectTestingMethodsForClass
{
    /**
     * @param class-string $class
     * @return array<int, TestingMethod>
     *
     * @throws ReflectionException
     */
    public static function run(string $class): array
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $parsedFile = $parser->parse(ClassDiscovery::source($class));

        if (!$parsedFile) {
            return [];
        }

        $testingMethods = [];
        $reflectionClass = new ReflectionClass($class);
        $coveredRoutes = CollectCoversRouteAttribute::run($reflectionClass);

        foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $testingMethod = new TestingMethod($method, $reflectionClass);

            if (!$testingMethod->isValid()) {
                continue;
            }

            $methodNodeVisitor = new FindMethodNodeByNameVisitor($method->getName());

            (new NodeTraverser($methodNodeVisitor))->traverse($parsedFile);

            if (is_null($methodNodeVisitor->getNodeAccessor())) {
                continue;
            }

            $testingMethod->setNodeAccessor($methodNodeVisitor->getNodeAccessor());
            $testingMethod->markRouteOccurrence(...$coveredRoutes, ...CollectCoversRouteAttribute::run($method));
            $testingMethods[] = $testingMethod;
        }

        return $testingMethods;
    }
}
