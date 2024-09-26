<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

interface ExportInterface extends OutputInterface
{
    /** @param array<int, \MyDev\AuditRoutes\Aggregators\AggregatorInterface> $aggregators */
    public function setAggregators(array $aggregators): self;
}
