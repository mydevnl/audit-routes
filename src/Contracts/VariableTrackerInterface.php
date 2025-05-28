<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Contracts;

interface VariableTrackerInterface
{
    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function declareVariable(string $name, mixed $value): void;

    /**
     * @param string $key
     * @return string
     */
    public function getDeclaredVariable(string $key): string;

    /** @return void */
    public function resetDeclaredVariables(): void;
}
