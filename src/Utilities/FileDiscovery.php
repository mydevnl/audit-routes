<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Utilities;

use Generator;
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
        /** @var array<int, SplFileInfo> $result */
        $result = iterator_to_array(self::findFilesAsIterator($path, $extension));

        return $result;
    }

    /**
     * @param string $path
     * @param null | string $extension
     * @return Iterator
     */
    protected static function findFilesAsIterator(string $path, ?string $extension = null): Iterator
    {
        if (!is_dir($path)) {
            return new Generator();
        }

        $fileIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        if (is_null($extension)) {
            return $fileIterator;
        }

        return new RegexIterator($fileIterator, '/\\.' . $extension . '$/', RegexIterator::MATCH);
    }
}
