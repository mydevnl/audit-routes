<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traits;

use MyDev\AuditRoutes\Routes\RouteInterface;
use ReflectionClass;
use ReflectionMethod;

trait Auditable
{
    use ConditionalAuditable;
    use IgnoresRoutes;

    /** @var int $routeMethod */
    protected int $weight = 1;

    /** @var int $penalty */
    protected int $penalty = 0;

    /** @var int $limit */
    protected int $limit = PHP_INT_MAX;

    /**
     * @param ?array<int | string, mixed> $arguments
     * @return self
     */
    public static function make(?array $arguments = null): self
    {
        $self = new self();

        if (method_exists($self, 'setArguments')) {
            $self->setArguments($arguments);
        }

        return $self;
    }

    /**
     * @param RouteInterface $route
     * @return ?int
     */
    public function run(RouteInterface $route): ?int
    {
        if (!$this->validate($route)) {
            return null;
        }

        return $this->handle($route);
    }

    /**
     * @param int $score
     * @return int
     */
    public function getScore(int $score): int
    {
        if ($score === 0) {
            return $this->penalty;
        }

        return min($this->limit, $score * $this->weight);
    }

    /**
     * @param int $weight
     * @return self
     */
    public function setWeight(int $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @param int $penalty
     * @return self
     */
    public function setPenalty(int $penalty): self
    {
        $this->penalty = $penalty;

        return $this;
    }

    /**
     * @param int $limit
     * @return self
     */
    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param RouteInterface $route
     * @return bool
     */
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

    /** @return array<string, null | string | int> */
    public function toArray(): array
    {
        return [
            'class'   => get_class($this),
            'weight'  => $this->weight,
            'penalty' => $this->penalty,
            'limit'   => $this->limit === PHP_INT_MAX ? null : $this->limit,
        ];
    }

    /** @return array<string, null | string | int> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
