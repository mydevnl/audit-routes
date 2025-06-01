<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::swap(new Repository([
            'audit-routes' => [
                'tests' => [
                    'directory'      => 'tests',
                    'implementation' => \PHPUnit\Framework\TestCase::class,
                    'acting-methods' => [
                        'get',
                        'post',
                        'put',
                        'delete',
                    ],
                ],
            ],
        ]));
    }
}
