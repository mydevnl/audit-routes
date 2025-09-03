<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Entities;

use MyDev\AuditRoutes\Contracts\RouteOccurrenceTrackerInterface;
use MyDev\AuditRoutes\Contracts\VariableTrackerInterface;
use MyDev\AuditRoutes\Traits\TracksRouteOccurrences;
use MyDev\AuditRoutes\Traits\TracksVariables;
use ReflectionClass;
use ReflectionMethod;

class TestingMethod implements VariableTrackerInterface, RouteOccurrenceTrackerInterface
{
    use TracksVariables;
    use TracksRouteOccurrences;

    /**
     * @param ReflectionMethod $method
     * @param ReflectionClass $parent
     * @param null | NodeAccessor $nodeAccessor
     */
    public function __construct(
        protected ReflectionMethod $method,
        protected ReflectionClass $parent,
        protected ?NodeAccessor $nodeAccessor = null,
    ) {
    }

    /** @return string */
    public function getName(): string
    {
        return $this->method->name;
    }

    /**
     * @return NodeAccessor|null
     */
    public function getNodeAccessor(): ?NodeAccessor
    {
        return $this->nodeAccessor;
    }

    /**
     * @param NodeAccessor $nodeAccessor
     * @return self
     */
    public function setNodeAccessor(NodeAccessor $nodeAccessor): self
    {
        $this->nodeAccessor = $nodeAccessor;

        return $this;
    }

    /** @return bool */
    public function isValid(): bool
    {
        if ($this->method->class !== $this->parent->getName()) {
            return false;
        }

        if ($this->method->isStatic()) {
            return false;
        }

        return strval($this->method->getReturnType()) === 'void';
    }
}
