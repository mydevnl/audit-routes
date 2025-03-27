<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Utilities;

use Closure;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionException;

class ClassDiscovery
{
    /**
     * @param string|ReflectionClass<object> $class
     * @param string $directory
     * @return array<int, class-string>
     * @throws ReflectionException
     */
    public static function subclassesOf(string|ReflectionClass $class, string $directory = ''): array
    {
        return self::find(
            $directory,
            fn (ReflectionClass $reflectionClass): bool => $reflectionClass->isSubclassOf($class)
        );
    }

    /**
     * @param string|ReflectionClass<object> $class
     * @param string $directory
     * @return array<int, string>
     * @throws ReflectionException
     */
    public static function implemenationsOf(string|ReflectionClass $class, string $directory = ''): array
    {
        return self::find(
            $directory,
            fn (ReflectionClass $reflectionClass): bool => $reflectionClass->implementsInterface($class)
        );
    }

    /**
     * @param class-string|object $class
     * @return string
     * @throws ReflectionException
     */
    public static function source(string|object $class): string
    {
        return File::get((string) (new ReflectionClass($class))->getFileName());
    }

    /**
     * @param string $directory
     * @param Closure(ReflectionClass<object>): bool $callback
     * @return array<int, class-string>
     * @throws ReflectionException
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

    /**
     * @param string $filePath
     * @return class-string
     */
    protected static function toPsr4Namespace(string $filePath): string
    {
        $partialName = explode('/', str_replace((string) getcwd(), '', $filePath));
        $partialName = array_map(
            fn (string $folder): string => ucfirst($folder),
            $partialName
        );

        /** @var class-string $psr4Namespace */
        $psr4Namespace = implode('\\', str_replace('.php', '', $partialName));

        return $psr4Namespace;
    }
}
