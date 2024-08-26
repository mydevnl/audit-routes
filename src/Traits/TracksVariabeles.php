<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traits;

trait TracksVariabeles
{
    /** @var array<string, mixed> $declaredVariables */
    private array $declaredVariables = [];

    public function declareVariable(string $name, mixed $value): void
    {
        $this->declaredVariables[$name] = $value;
    }

    public function getDeclaredVariable(string $key): string
    {
        return strval($this->declaredVariables[$key] ?? null);
    }

    public function resetDeclaredVariables(): void
    {
        $this->declaredVariables = [];
    }
}
