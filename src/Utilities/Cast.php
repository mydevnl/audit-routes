<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Utilities;

use InvalidArgumentException;

class Cast
{
    /**
     * @param mixed $value
     * @return array<int | string, mixed>
     */
    public static function array(mixed $value): array
    {
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        if (is_null($value)) {
            return [];
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException('Value can not be cast to an array.');
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public static function string(mixed $value): string
    {
        if (is_object($value) && method_exists($value, 'toString')) {
            return $value->toString();
        }

        if (!is_scalar($value) && !is_null($value)) {
            throw new InvalidArgumentException('Value can not be cast to a string.');
        }

        return strval($value);
    }

    /**
     * @param mixed $value
     * @return int
     */
    public static function int(mixed $value): int
    {
        if (!is_scalar($value) && !is_null($value)) {
            throw new InvalidArgumentException('Value can not be cast to a string.');
        }

        return intval($value);
    }

    /**
     * @param mixed $value
     * @return float
     */
    public static function float(mixed $value): float
    {
        if (!is_scalar($value) && !is_null($value)) {
            throw new InvalidArgumentException('Value can not be cast to a string.');
        }

        return floatval($value);
    }
}
