<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Utilities;

use Closure;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class ClassDiscovery
{
    /** @return array<int, string> */
    public static function subclassesOf(string|ReflectionClass $class, string $directory = ''): array
    {
        return self::find(
            $directory,
            fn (ReflectionClass $reflectionClass): bool => $reflectionClass->isSubclassOf($class)
        );
    }

    /** @return array<int, string> */
    public static function implemenationsOf(string|ReflectionClass $class, string $directory = ''): array
    {
        return self::find(
            $directory,
            fn (ReflectionClass $reflectionClass): bool => $reflectionClass->implementsInterface($class)
        );
    }

    /** @param class-string|object $class */
    public static function source(string|object $class): string
    {
        return File::get((new ReflectionClass($class))->getFileName());
    }

    /**
     * @param  Closure(ReflectionClass): bool $callback
     * @return array<int, string>
     */
    protected static function find(string $directory, Closure $callback): array
    {
        $found = [];

        foreach (File::allFiles(getcwd() . '/' . $directory) as $file) {
            $namespacedClassName = self::toPSR4Namespace($file->getPathName());

            if ($callback(new ReflectionClass($namespacedClassName))) {
                $found[] = $namespacedClassName;
            }
        }

        return $found;
    }

    protected static function toPsr4Namespace(string $filePath): string
    {
        $partialName = explode('/', str_replace(getcwd(), '', $filePath));
        $partialName = array_map(
            fn (string $folder): string => ucfirst($folder),
            $partialName
        );

        return implode('\\', str_replace('.php', '', $partialName));
    }
}
