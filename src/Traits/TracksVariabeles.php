<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traits;

trait TracksVariabeles
{
    /** @var array<string, mixed> $declaredVariables */
    protected array $declaredVariables = [];

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function declareVariable(string $name, mixed $value): void
    {
        $this->declaredVariables[$name] = $value;
    }

    /**
     * @param string $key
     * @return string
     */
    public function getDeclaredVariable(string $key): string
    {
        return strval($this->declaredVariables[$key] ?? null);
    }

    /** @return void */
    public function resetDeclaredVariables(): void
    {
        $this->declaredVariables = [];
    }
}
