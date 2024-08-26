<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Entities;

use Exception;
use MyDev\AuditRoutes\Auditors\AuditorInterface;
use MyDev\AuditRoutes\Enums\AuditStatus;
use MyDev\AuditRoutes\Repositories\RouteInterface;
use Stringable;

class AuditedRoute implements Stringable
{
    private int $score = 0;

    public function __construct(private readonly RouteInterface $route, private readonly int $benchmark)
    {
    }

    public static function for(RouteInterface $route, int $benchmark = 0): self
    {
        return new self($route, $benchmark);
    }

    /** @param array<class-string<AuditorInterface>, int> | array<int, AuditorInterface|class-string<AuditorInterface>> $auditors */
    public function audit(array $auditors): self
    {
        $this->score = 0;

        foreach ($auditors as $key => $value) {
            $this->score += $this->buildAuditor($key, $value)->run($this->route);
        }

        return $this;
    }

    public function getName(): string
    {
        return $this->route->getName();
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getStatus(): AuditStatus
    {
        if ($this->score < $this->benchmark) {
            return AuditStatus::Failed;
        }

        return AuditStatus::Ok;
    }

    public function hasStatus(AuditStatus $status): bool
    {
        return $this->getStatus() === $status;
    }

    public function __toString(): string
    {
        return "[ {$this->getStatus()->value} ]" . $this->getName() . ' ⇨ ' . $this->score;
    }

    /**
     * @param class-string<AuditorInterface> | int                    $key
     * @param class-string<AuditorInterface> | AuditorInterface | int $value
     */
    protected function buildAuditor(string | int $key, string | AuditorInterface | int $value): AuditorInterface
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

        throw new Exception('Could not instantiate AuditorInterface');
    }
}
