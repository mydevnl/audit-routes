<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Entities;

use Closure;
use Iterator;
use MyDev\AuditRoutes\Aggregators\AggregatorInterface;
use UnexpectedValueException;

class AuditedRouteCollection implements Iterator
{
    /** @var int $currentIndex */
    protected int $currentIndex = 0;

    /**
     * @param array<int, AuditedRoute> $items
     * @return void
     */
    public function __construct(protected array $items = [])
    {
    }

    /**
     * @param array<int, AuditedRoute> $items
     * @return self
     */
    public static function make(array $items = []): self
    {
        return new self($items);
    }

    /** @return array<int, AuditedRoute> */
    public function get(): array
    {
        return $this->items;
    }

    /** @return int */
    public function count(): int
    {
        return count($this->items);
    }

    /** @return bool */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /** @return bool */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /** @return self */
    public function sort(bool $ascending = true): self
    {
        usort($this->items, function (AuditedRoute $current, AuditedRoute $previous) use ($ascending): int {
            if ($current->getScore() === $previous->getScore()) {
                return strval($current) < strval($previous) ? -1 : 1;
            }

            $direction = $ascending ? -1 : 1;

            return $current->getScore() < $previous->getScore() ? $direction : ($direction * -1);
        });

        return $this;
    }


    /**
     * @param AuditedRoute $auditedRoute
     * @return self
     */
    public function push(AuditedRoute $auditedRoute): self
    {
        array_push($this->items, $auditedRoute);

        return $this;
    }

    /**
     * @param Closure(AuditedRoute): void $callback
     * @return self
     */
    public function each(Closure $callback): self
    {
        array_walk($this->items, $callback);

        return $this;
    }

    /**
     * @param Closure(AuditedRoute): mixed $callback
     * @return array<int, mixed>
     */
    public function map(Closure $callback): array
    {
        return array_map($callback, $this->items);
    }

    /**
     * @param string $field
     * @param string $value
     * @throws UnexpectedValueException
     * @return self
     */
    public function where(string $field, string $value): self
    {
        /** @var array<Closure(AuditedRoute): bool> $comparators */
        $comparators = [
            'status' => fn (AuditedRoute $auditedRoute): bool => $auditedRoute->getStatus()->value === $value,
            'name'   => fn (AuditedRoute $auditedRoute): bool => str_contains($value, $auditedRoute->getDisplayName()),
        ];

        if (!isset($comparators[$field])) {
            throw new UnexpectedValueException('Unsupported field name: ' . $field);
        }

        $self = new self();

        $this->each(function (AuditedRoute $auditedRoute) use ($comparators, $self, $field): void {
            if ($comparators[$field]($auditedRoute)) {
                $self->push($auditedRoute);
            }
        });

        return $self;
    }

    /**
     * @param AggregatorInterface ...$aggregators
     * @return array<int, AggregatorInterface>
     */
    public function aggregate(AggregatorInterface ...$aggregators): array
    {
        foreach ($aggregators as $aggregator) {
            $this->each(fn (AuditedRoute $auditedRoute) => $aggregator->visit($auditedRoute));
            $aggregator->after();
        }

        return $aggregators;
    }

    /** @return AuditedRoute */
    public function current(): AuditedRoute
    {
        return $this->items[$this->currentIndex];
    }

    /** @return void */
    public function next(): void
    {
        $this->currentIndex++;
    }

    /** @return int */
    public function key(): int
    {
        return $this->currentIndex;
    }

    /** @return bool */
    public function valid(): bool
    {
        return isset($this->items[$this->currentIndex]);
    }

    /** @return void */
    public function rewind(): void
    {
        $this->currentIndex = 0;
    }
}
