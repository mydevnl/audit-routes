<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\Config\RectorConfig;
use Rector\Php56\Rector\FuncCall\PowToExpRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;

return RectorConfig::configure()
    ->withPhpSets(php81: true)
    ->withAttributesSets()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/docs/examples',
    ])
    ->withCache(
        cacheDirectory: __DIR__ . '/.cache/rector',
        cacheClass: FileCacheStorage::class,
    )
    ->withSkip([
        __DIR__ . '/.cache',
        __DIR__ . '/.dev',
        __DIR__ . '/.github',
        __DIR__ . '/resources',
        __DIR__ . '/vendor',
        ClosureToArrowFunctionRector::class,
        PowToExpRector::class,
        OptionalParametersAfterRequiredRector::class,
        NullToStrictStringFuncCallArgRector::class => [
            __DIR__ . '/config',
        ],
    ]);
