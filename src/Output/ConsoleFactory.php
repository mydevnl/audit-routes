<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Output;

use Symfony\Component\Console\Style\OutputStyle;

class ConsoleFactory
{
    public static function build(int $verbosity, OutputStyle $output): OutputInterface
    {
        return match ($verbosity) {
            32  => new ConsoleStatus($output),
            64  => new ConsoleResult($output),
            128 => new ConsoleTable($output),
            256 => new ConsoleMultiOutput([
                ConsoleTable::class,
                ConsoleResult::class,
            ], $output),
            default => new ConsoleStatus($output),
        };
    }
}
