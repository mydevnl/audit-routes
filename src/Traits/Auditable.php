<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traits;

use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/** @mixin AuditorInterface */
trait Auditable
{
    use ConditionalAuditable;
    use IgnoresRoutes;
    use Nameable;

    /** @var int $routeMethod */
    protected int $weight = 1;

    /** @var int $penalty */
    protected int $penalty = 0;

    /** @var int $limit */
    protected int $limit = PHP_INT_MAX;

    /**
     * @param null | array<int | string, mixed> $arguments
     * @return AuditorInterface
     * @throws ReflectionException
     */
    public static function make(?array $arguments = null): AuditorInterface
    {
        $self = new self();

        if (method_exists($self, 'setArguments')) {
            $self->setArguments($arguments);
        }

        return $self;
    }

    /**
     * @param RouteInterface $route
     * @return null | int
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
     * @return static
     */
    public function setWeight(int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @param int $penalty
     * @return static
     */
    public function setPenalty(int $penalty): static
    {
        $this->penalty = $penalty;

        return $this;
    }

    /**
     * @param int $limit
     * @return static
     */
    public function setLimit(int $limit): static
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
            'name'    => $this->getName() ?? get_class($this),
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
