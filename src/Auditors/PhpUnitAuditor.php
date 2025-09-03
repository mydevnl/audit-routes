<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use Closure;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use MyDev\AuditRoutes\Actions\CollectTestingMethods;
use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Contracts\RouteOccurrenceTrackerInterface;
use MyDev\AuditRoutes\Contracts\VariableTrackerInterface;
use MyDev\AuditRoutes\Entities\TestingMethod;
use MyDev\AuditRoutes\Traits\Auditable;
use MyDev\AuditRoutes\Traits\TracksRouteOccurrences;
use MyDev\AuditRoutes\Traits\TracksVariables;
use MyDev\AuditRoutes\Utilities\Cast;
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

    /** @var bool $isParsed */
    protected bool $isParsed = false;

    /**
     * @param RouteInterface $route
     * @return int
     */
    public function handle(RouteInterface $route): int
    {
        if (!$this->isParsed) {
            $this->isParsed = true;
            if (empty($this->testingMethods)) {
                $this->setTestingMethods();
            }

            foreach ($this->testingMethods as $testingMethod) {
                $this->parseTestingMethod($testingMethod);
            }
        }

        return $this->getScore($this->getRouteOccurrence($route->getIdentifier()));
    }

    /**
     * @param array<int, TestingMethod>|null $testingMethods
     * @return $this
     */
    public function setTestingMethods(?array $testingMethods = null): self
    {
        if (is_null($testingMethods)) {
            $directory = Cast::string(Config::get('audit-routes.tests.directory'));
            $testingMethods = CollectTestingMethods::run($directory);
        }

        $this->testingMethods = $testingMethods;

        return $this;
    }

    /**
     * @param null | array<int | string, mixed> $arguments
     * @return self
     *
     * @throws InvalidArgumentException
     */
    public function setArguments(?array $arguments): self
    {
        foreach ($arguments ?? [] as $argument) {
            if (!$argument instanceof Closure) {
                throw new InvalidArgumentException('Arguments must be an instance of Closure.');
            }

            try {
                $reflection = new ReflectionFunction($argument);
            } catch (ReflectionException) {
                throw new InvalidArgumentException('Arguments closure does not exist.');
            }

            if (strval($reflection->getReturnType()) !== 'bool') {
                throw new InvalidArgumentException('Arguments closure should return a boolean.');
            }

            $parameters = $reflection->getParameters();

            if (empty($parameters) || strval($parameters[0]->getType()) !== ClassMethod::class) {
                throw new InvalidArgumentException('First argument must be an instance of ClassMethod.');
            }
        }
        /** @var null | array<int, Closure(ClassMethod): bool> $arguments */
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
            $node = $testingMethod->getNodeAccessor()?->getNode();
            if (!$node instanceof ClassMethod || !$testCondition($node)) {
                return;
            }
        }

        $testingMethod->getNodeAccessor()?->traverse(new PhpUnitMethodVisitor($this));

        $this->markRouteOccurrences($testingMethod->getRouteOccurrences());
    }
}
