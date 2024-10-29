<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traits;

/** @mixin \MyDev\AuditRoutes\Aggregators\AggregatorInterface */
trait Aggregateable
{
    use Nameable;

    /** @return float | array<int, \MyDev\AuditRoutes\Aggregators\AggregatorInterface> */
    public function getResult(): float | array
    {
        if (!property_exists($this, 'result')) {
            return 0;
        }

        return $this->result;
    }

    /** @return void */
    public function after(): void {}

    /** @return string */
    public function getAggregator(): string
    {
        $classParts = explode('\\', get_class($this));

        $snakeCase = preg_replace_callback(
            '/[A-Z]/',
            fn(array $matches): string => '_' . strtolower($matches[0]),
            array_pop($classParts),
        );

        return ltrim($snakeCase, '_');
    }

    /** @return array<string, string | float> */
    public function toArray(): array
    {
        return [
            'aggregator' => $this->getAggregator(),
            'name'       => $this->getName(),
            'result'     => $this->getResult(),
        ];
    }

    /** @return array<string, string | float> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
