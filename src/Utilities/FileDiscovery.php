<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Utilities;

use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

class FileDiscovery
{
    /**
     * @param string $path
     * @param null | string $extension
     * @return array<int, SplFileInfo>
     */
    public static function find(string $path, ?string $extension = null): array
    {
        $iterator = self::findFilesAsIterator($path, $extension);
        if (is_null($iterator)) {
            return [];
        }

        /** @var array<int, SplFileInfo> $result */
        $result = iterator_to_array($iterator);

        return $result;
    }

    /**
     * @param string $path
     * @param null | string $extension
     * @return null | Iterator
     */
    protected static function findFilesAsIterator(string $path, ?string $extension = null): ?Iterator
    {
        if (!is_dir($path)) {
            return null;
        }

        $fileIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        if (is_null($extension)) {
            return $fileIterator;
        }

        return new RegexIterator($fileIterator, '/\\.' . $extension . '$/', RegexIterator::MATCH);
    }
}
