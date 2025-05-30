<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use InvalidArgumentException;
use MyDev\AuditRoutes\Contracts\AuditorInterface;

class AuditorFactory
{
    /**
     * @param class-string<AuditorInterface> | int                    $key
     * @param class-string<AuditorInterface> | AuditorInterface | int $value
     *
     * @throws InvalidArgumentException
     *
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
            return $value::make();
        }

        throw new InvalidArgumentException('Could not instantiate AuditorInterface');
    }

    /**
     * @param  array<class-string<AuditorInterface>, int> | array<int, AuditorInterface|class-string<AuditorInterface>> $auditors
     *
     * @throws InvalidArgumentException
     *
     * @return array<int, AuditorInterface>
     */
    public static function buildMany(array $auditors): array
    {
        $initialisedAuditors = [];

        foreach ($auditors as $key => $value) {
            $initialisedAuditors[] = self::build($key, $value);
        }

        return $initialisedAuditors;
    }
}
