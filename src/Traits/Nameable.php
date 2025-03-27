<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traits;

trait Nameable
{
    /** @var ?string $name */
    protected ?string $name = null;

    /**
     * @param null | string $name
     * @return static
     */
    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /** @return null | string */
    public function getName(): ?string
    {
        return $this->name;
    }
}
