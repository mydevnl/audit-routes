<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traits;

use MyDev\AuditRoutes\Repositories\RouteInterface;
use ReflectionClass;
use ReflectionMethod;

trait Auditable
{
    use ConditionalAuditable;
    use IgnoresRoutes;

    protected int $weight = 1;
    protected int $penalty = 0;
    protected int $limit = PHP_INT_MAX;

    public static function make(?array $arguments = null): self
    {
        $self = new self();

        if (method_exists($self, 'setArguments')) {
            $self->setArguments($arguments);
        }

        return $self;
    }

    public function run(RouteInterface $route): int
    {
        if (!$this->validate($route)) {
            return 0;
        }

        return $this->handle($route);
    }

    public function getScore(int $score): int
    {
        if ($score === 0) {
            return $this->penalty;
        }

        return min($this->limit, $score * $this->weight);
    }

    public function setWeight(int $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function setPenalty(int $penalty): self
    {
        $this->penalty = $penalty;

        return $this;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function validate(RouteInterface $route): bool
    {
        $class = new ReflectionClass($this);

        foreach ($class->getMethods(ReflectionMethod::IS_PROTECTED) as $method) {
            $methodName = $method->getShortName();
            if (!str_starts_with($methodName, 'validate') || $methodName === 'validate') {
                continue;
            }

            $parameter = ($method->getParameters()[0] ?? null)?->getType();
            if (!$parameter || !method_exists($parameter, 'getName') || $parameter->getName() !== RouteInterface::class) {
                continue;
            }

            if (!$this->{$methodName}($route)) {
                return false;
            }
        }

        return true;
    }
}
