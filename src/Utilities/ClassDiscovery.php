<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Utilities;

use Closure;
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
        return file_get_contents((string) (new ReflectionClass($class))->getFileName());
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
        $absolutePath = getcwd() . DIRECTORY_SEPARATOR . $directory;

        foreach (FileDiscovery::find($absolutePath, 'php') as $file) {
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
        $relativePath = str_replace((string) getcwd(), '', $filePath);
        $partialNames = array_map(
            fn (string $directory): string => ucfirst($directory),
            explode(DIRECTORY_SEPARATOR, $relativePath)
        );

        /** @var class-string $psr4Namespace */
        $psr4Namespace = substr(implode('\\', $partialNames), 0, -4);

        return $psr4Namespace;
    }
}
