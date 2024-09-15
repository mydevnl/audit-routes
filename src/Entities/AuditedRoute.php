<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Entities;

use Exception;
use JsonSerializable;
use MyDev\AuditRoutes\Auditors\AuditorInterface;
use MyDev\AuditRoutes\Enums\AuditStatus;
use MyDev\AuditRoutes\Repositories\RouteInterface;
use Stringable;

class AuditedRoute implements Stringable, JsonSerializable
{
    private int $score = 0;
    private array $results = [];

    /**
     * @param RouteInterface $route
     * @param int $benchmark
     * @return void
     */
    public function __construct(private readonly RouteInterface $route, private readonly int $benchmark)
    {
    }

    /**
     * @param RouteInterface $route
     * @param int $benchmark
     * @return self
     */
    public static function for(RouteInterface $route, int $benchmark = 0): self
    {
        return new self($route, $benchmark);
    }

    /**
     * @param array<class-string<AuditorInterface>, int> | array<int, AuditorInterface|class-string<AuditorInterface>> $auditors
     * @return self
     */
    public function audit(array $auditors): self
    {
        $this->score = 0;

        foreach ($auditors as $key => $value) {
            $auditor = $this->buildAuditor($key, $value);
            $result = $auditor->run($this->route);

            $this->score += $result;

            $this->results[] = [
                'auditor' => $auditor,
                'result'  => $result,
            ];
        }

        return $this;
    }

    /** @return string */
    public function getName(): string
    {
        return $this->route->getName();
    }

    /** @return int */
    public function getScore(): int
    {
        return $this->score;
    }

    /** @return AuditStatus */
    public function getStatus(): AuditStatus
    {
        if ($this->score < $this->benchmark) {
            return AuditStatus::Failed;
        }

        return AuditStatus::Ok;
    }

    /**
     * @param AuditStatus $status
     * @return bool
     */
    public function hasStatus(AuditStatus $status): bool
    {
        return $this->getStatus() === $status;
    }

    /** @return string */
    public function __toString(): string
    {
        return $this->getName();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name'      => $this->getName(),
            'score'     => $this->score,
            'status'    => $this->getStatus()->value,
            'failed'    => $this->hasStatus(AuditStatus::Failed),
            'benchmark' => $this->benchmark,
            'auditors'  => $this->results,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param class-string<AuditorInterface> | int                    $key
     * @param class-string<AuditorInterface> | AuditorInterface | int $value
     * @return AuditorInterface
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
