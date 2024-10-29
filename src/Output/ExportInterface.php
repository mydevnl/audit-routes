<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

interface ExportInterface extends OutputInterface
{
    /**
     * @param array<int, \MyDev\AuditRoutes\Aggregators\AggregatorInterface> $aggregators
     * @return self
     */
    public function setAggregators(array $aggregators): self;

    /**
     * @param null | string $filename
     * @return self
     */
    public function setFilename(?string $filename): self;
}
