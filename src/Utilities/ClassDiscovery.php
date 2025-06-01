<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Utilities;

use Closure;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

class ClassDiscovery
{
    /**
     * @param string|ReflectionClass<object> $class
     * @param string $directory
     * @return array<int, class-string>
     */
    public static function subclassesOf(string|ReflectionClass $class, string $directory = ''): array
    {
        return self::find(
            $directory,
            fn (ReflectionClass $reflectionClass): bool => $reflectionClass->isSubclassOf($class),
        );
    }

    /**
     * @param string|ReflectionClass<object> $class
     * @param string $directory
     * @return array<int, string>
     */
    public static function implemenationsOf(string|ReflectionClass $class, string $directory = ''): array
    {
        return self::find(
            $directory,
            fn (ReflectionClass $reflectionClass): bool => $reflectionClass->implementsInterface($class),
        );
    }

    /**
     * @param class-string|object $class
     * @return string
     *
     * @throws ReflectionException
     */
    public static function source(string|object $class): string
    {
        return (string) file_get_contents((string) (new ReflectionClass($class))->getFileName());
    }

    /**
     * @param string $directory
     * @param Closure(ReflectionClass<object>): bool $callback
     * @return array<int, class-string>
     */
    protected static function find(string $directory, Closure $callback): array
    {
        if (!str_starts_with($directory, DIRECTORY_SEPARATOR)) {
            $directory = getcwd() . DIRECTORY_SEPARATOR . $directory;
        }

        $found = [];
        foreach (FileDiscovery::find($directory, 'php') as $file) {
            $namespacedClassName = self::toPSR4Namespace($file->getPathName());

            try {
                if ($callback(new ReflectionClass($namespacedClassName))) {
                    $found[] = $namespacedClassName;
                }
            } catch (ReflectionException) {
                continue;
            }
        }

        return $found;
    }

    /**
     * @param string $filePath
     * @return class-string
     *
     * @throws RuntimeException
     */
    protected static function toPsr4Namespace(string $filePath): string
    {
        $absoluteFilePath = realpath($filePath);
        if (!str_ends_with($filePath, '.php') || !is_string($absoluteFilePath)) {
            throw new RuntimeException("File does not appear to be a resolvable PHP file: {$filePath}");
        }

        $autoload = [];

        /** @var array<string, mixed> $composer */
        $composer = json_decode((string) file_get_contents('composer.json'), true);

        foreach (['autoload', 'autoload-dev'] as $autoloader) {
            if (isset($composer[$autoloader]) && is_array($composer[$autoloader])) {
                $autoload = array_merge($autoload, Cast::array($composer[$autoloader]['psr-4']));
            }
        }

        foreach ($autoload as $namespacePrefix => $baseDir) {
            $absoluteBaseDir = realpath(Cast::string($baseDir));
            if (!is_string($absoluteBaseDir)) {
                continue;
            }
            if (!str_starts_with($absoluteFilePath, $absoluteBaseDir)) {
                continue;
            }

            $relativePath = ltrim(substr($absoluteFilePath, strlen($absoluteBaseDir)), DIRECTORY_SEPARATOR);
            $classPath = str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relativePath);

            /** @var class-string $psr4Namespace */
            $psr4Namespace = rtrim((string) $namespacePrefix, '\\') . '\\' . $classPath;

            return $psr4Namespace;
        }

        throw new RuntimeException("Could not determine PSR-4 namespace for {$filePath}");
    }
}
