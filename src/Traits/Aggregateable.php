<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traits;

use MyDev\AuditRoutes\Contracts\AggregatorInterface;

/** @mixin AggregatorInterface */
trait Aggregateable
{
    use Nameable;

    /** @return null | float | array<int | string, AggregatorInterface> */
    public function getResult(): null | float | array
    {
        if (!property_exists($this, 'result')) {
            return 0;
        }

        return $this->result;
    }

    /** @return void */
    public function after(): void
    {
    }

    /** @return string */
    public function getAggregator(): string
    {
        $classParts = explode('\\', $this::class);

        $snakeCase = (string) preg_replace_callback(
            '/[A-Z]/',
            fn (array $matches): string => '_' . strtolower($matches[0]),
            array_pop($classParts),
        );

        return ltrim($snakeCase, '_');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'aggregator' => $this->getAggregator(),
            'name'       => $this->getName(),
            'result'     => $this->getResult(),
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
