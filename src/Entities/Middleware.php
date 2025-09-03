<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Entities;

use Closure;
use InvalidArgumentException;

class Middleware
{
    /**
     * @param Closure|array<int | string, mixed>|string $value
     * @param string $resolver
     * @param string $alias
     * @param array<int, string> $attributes
     */
    public function __construct(
        protected readonly Closure | array | string $value,
        protected readonly string $resolver,
        protected readonly string $alias,
        protected readonly array $attributes = [],
    ) {
    }

    /**
     * @param Closure|array<int | string, mixed>|string $value
     * @param string|null $alias
     * @return self
     */
    public static function from(Closure | array | string $value, ?string $alias = null): self
    {
        $stringValue = is_string($value) ? $value : $alias;

        if (!is_string($stringValue)) {
            throw new InvalidArgumentException('Could not determine middleware alias.');
        }

        if (!str_contains($stringValue, ':')) {
            return new self($value, $stringValue, $stringValue);
        }

        [$resolver, $paramsString] = explode(':', $stringValue, 2);

        return new self($value, $resolver, $resolver, explode(',', $paramsString));
    }

    /** @return array<int, string> */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /** @return string */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @param string | Middleware ...$compares
     * @return bool
     */
    public function is(string | Middleware ...$compares): bool
    {
        foreach ($compares as $compare) {
            if (!$compare instanceof Middleware) {
                $compare = Middleware::from($compare);
            }

            if ($this->resolver !== $compare->resolver && $this->alias !== $compare->alias) {
                continue;
            }

            $missingAttributes = array_filter(
                $compare->attributes,
                fn (string $attribute): bool => !in_array($attribute, $this->attributes),
            );

            if (empty($missingAttributes)) {
                return true;
            }
        }

        return false;
    }
}
