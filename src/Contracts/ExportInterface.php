<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Contracts;

interface ExportInterface extends OutputInterface
{
    /**
     * @param array<int, AggregatorInterface> $aggregators
     * @return self
     */
    public function setAggregators(array $aggregators): self;

    /**
     * @param null | string $filename
     * @return self
     */
    public function setFilename(?string $filename): self;
}
