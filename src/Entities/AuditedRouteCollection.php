<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Entities;

use Closure;
use Iterator;
use UnexpectedValueException;

class AuditedRouteCollection implements Iterator
{
    private int $currentIndex = 0;

    /**
     * @param array<int, AuditedRoute> $items
     * @return void
     */
    public function __construct(private array $items = [])
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

    /** @return array */
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
        $this->items[] = $auditedRoute;

        return $this;
    }

    /**
     * @param Closure(AuditedRoute): void $callback
     * @return self
     */
    public function each(Closure $callback): self
    {
        foreach ($this->items as $item) {
            $callback($item);
        }

        return $this;
    }

    /**
     * @param Closure(AuditedRoute): mixed $callback
     * @return array<int, mixed>
     */
    public function map(Closure $callback): array
    {
        $result = [];

        foreach ($this->items as $item) {
            $result[] = $callback($item);
        }

        return $result;
    }

    /**
     * @param string $field
     * @param string $value
     * @return self
     */
    public function where(string $field, string $value): self
    {
        $self = new self();

        /** @var array<Closure(AuditedRoute): bool> $comparators */
        $comparators = [
            'status' => fn (AuditedRoute $auditedRoute): bool => $auditedRoute->getStatus()->value === $value,
            'name'   => fn (AuditedRoute $auditedRoute): bool => str_contains($value, $auditedRoute->getName()),
        ];

        if (!isset($comparators[$field])) {
            throw new UnexpectedValueException('Unsupported field name: ' . $field);
        }

        foreach ($this->items as $item) {
            if ($comparators[$field]($item)) {
                $self->push($item);
            }
        }

        return $self;
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
