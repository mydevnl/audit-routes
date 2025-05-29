<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Entities;

use InvalidArgumentException;
use JsonSerializable;
use MyDev\AuditRoutes\Auditors\AuditorFactory;
use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Contracts\RouteInterface;
use MyDev\AuditRoutes\Enums\AuditStatus;
use Stringable;

class AuditedRoute implements Stringable, JsonSerializable
{
    /** @var int $score */
    protected int $score = 0;

    /** @var array<int | string, mixed> $results */
    protected array $results = [];

    /**
     * @param RouteInterface $route
     * @param int $benchmark
     * @return void
     */
    public function __construct(protected RouteInterface $route, protected readonly int $benchmark)
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
     *
     * @throws InvalidArgumentException
     *
     * @return self
     */
    public function audit(array $auditors): self
    {
        $this->score = 0;

        foreach ($auditors as $key => $value) {
            $auditor = AuditorFactory::build($key, $value);
            $result = $auditor->run($this->route);

            if (is_null($result)) {
                continue;
            }

            $this->score += $result;

            $this->results[] = [
                'auditor' => $auditor,
                'result'  => $result,
            ];
        }

        return $this;
    }

    /** @return string */
    public function getDisplayName(): string
    {
        $name = $this->route->getName();

        if ($name) {
            return $name;
        }

        $uri = $this->route->getUri();

        if (!str_starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    /** @return int */
    public function getScore(): int
    {
        return $this->score;
    }

    /** @return int */
    public function getBenchmark(): int
    {
        return $this->benchmark;
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
        return $this->getDisplayName();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name'      => $this->getDisplayName(),
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
}
