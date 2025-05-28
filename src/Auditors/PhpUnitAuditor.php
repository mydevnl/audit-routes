<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use Closure;
use InvalidArgumentException;
use MyDev\AuditRoutes\Actions\CollectTestingMethods;
use MyDev\AuditRoutes\Contracts\RouteOccurrenceTrackerInterface;
use MyDev\AuditRoutes\Contracts\VariableTrackerInterface;
use MyDev\AuditRoutes\Entities\TestingMethod;
use MyDev\AuditRoutes\Routes\RouteInterface;
use MyDev\AuditRoutes\Traits\Auditable;
use MyDev\AuditRoutes\Traits\TracksRouteOccurrences;
use MyDev\AuditRoutes\Traits\TracksVariables;
use MyDev\AuditRoutes\Visitors\PhpUnitMethodVisitor;
use PhpParser\Node\Stmt\ClassMethod;
use ReflectionException;
use ReflectionFunction;

class PhpUnitAuditor implements AuditorInterface, VariableTrackerInterface, RouteOccurrenceTrackerInterface
{
    use Auditable;
    use TracksVariables;
    use TracksRouteOccurrences;

    /** @var array<int, TestingMethod> $testingMethods */
    protected array $testingMethods = [];

    /** @var array<int, Closure(ClassMethod): bool> $testConditions */
    protected array $testConditions = [];

    public function __construct()
    {
        $this->testingMethods = CollectTestingMethods::run();

        $this->routeOccurrences = [];
        foreach ($this->testingMethods as $testingMethod) {
            $this->parseTestingMethod($testingMethod);
            $this->markRouteOccurrences($testingMethod->getRouteOccurrences());
        }
    }

    /**
     * @param RouteInterface $route
     * @return int
     * @throws ReflectionException
     */
    public function handle(RouteInterface $route): int
    {
        return $this->getScore($this->getRouteOccurrence($route->getIdentifier()));
    }

    /**
     * @param null | array<int | string, mixed> $arguments
     * @return self
     * @throws ReflectionException
     */
    public function setArguments(?array $arguments): self
    {
        if ($arguments){
            $this->validateArguments($arguments);
        }
        $this->testConditions = $arguments ?? [];

        return $this;
    }

    /**
     * @param TestingMethod $testingMethod
     * @return void
     */
    protected function parseTestingMethod(TestingMethod $testingMethod): void
    {
        foreach ($this->testConditions as $testCondition) {
            $node = $testingMethod->getNodeAccessor()->getNode();
            if (!$node instanceof ClassMethod || !$testCondition($node)) {
                return;
            }
        }

        $testingMethod->getNodeAccessor()->traverse(new PhpUnitMethodVisitor($testingMethod));
    }

    /**
     * @param array<int | string, mixed> $arguments
     * @return void
     * @throws InvalidArgumentException|ReflectionException
     */
    protected function validateArguments(array $arguments): void
    {
        foreach ($arguments as $argument) {
            if (!$argument instanceof Closure) {
                throw new InvalidArgumentException('Arguments must be an instance of Closure.');
            }

            $reflection = new ReflectionFunction($argument);

            if (strval($reflection->getReturnType()) !== 'bool') {
                throw new InvalidArgumentException('Arguments closure should return a boolean.');
            }

            $parameters = $reflection->getParameters();

            if (empty($parameters) || strval($parameters[0]->getType()) !== ClassMethod::class) {
                throw new InvalidArgumentException('First argument must be an instance of ClassMethod.');
            }
        }
    }
}
