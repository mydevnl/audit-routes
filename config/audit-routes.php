<?php

declare(strict_types=1);

return [
    'ignored-routes' => [
        'telescope*',
        'debugbar.*',
        'ignition.*',
        'sanctum.*',
    ],
    'benchmark' => 0,
    'tests'     => [
        'directory'      => 'tests',
        'implementation' => \Tests\TestCase::class,
        'acting-methods' => [
            'get',
            'getJson',
            'post',
            'postJson',
            'put',
            'putJson',
            'patch',
            'patchJson',
            'delete',
            'deleteJson',
            'call',
            'json',
        ],
    ],
    'output' => [
        'directory'            => 'storage/exports/audit-routes',
        'html-index-template'  => 'audit-routes::output.index',
        'html-report-template' => 'audit-routes::output.report',
    ],
];
