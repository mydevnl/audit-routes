<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use InvalidArgumentException;

class AuditorFactory
{
    /**
     * @param class-string<AuditorInterface> | int                    $key
     * @param class-string<AuditorInterface> | AuditorInterface | int $value
     * @throws InvalidArgumentException
     * @return AuditorInterface
     */
    public static function build(string | int $key, string | AuditorInterface | int $value): AuditorInterface
    {
        if ($value instanceof AuditorInterface) {
            return $value;
        }

        if (is_string($key) && is_int($value)) {
            return $key::make()->setWeight($value);
        }

        if (is_int($key) && is_string($value)) {
            return $value::make()->setWeight($key);
        }

        throw new InvalidArgumentException('Could not instantiate AuditorInterface');
    }
}
