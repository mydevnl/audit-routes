<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Entities;

use JsonSerializable;
use MyDev\AuditRoutes\Contracts\AuditorInterface;

class AuditorResult implements JsonSerializable
{
    /**
     * @param AuditorInterface $auditor
     * @param int $result
     */
    public function __construct(protected AuditorInterface $auditor, protected int $result)
    {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'result'  => $this->result,
            'auditor' => $this->auditor,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
