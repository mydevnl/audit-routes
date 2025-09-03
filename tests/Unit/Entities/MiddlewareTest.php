<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Entities;

use InvalidArgumentException;
use MyDev\AuditRoutes\Entities\Middleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase
{
    #[Test]
    public function it_constructs_with_a_string(): void
    {
        $middleware = Middleware::from('auth');

        $this->assertInstanceOf(Middleware::class, $middleware);
        $this->assertSame('auth', $middleware->getAlias());
    }

    #[Test]
    public function it_constructs_with_a_closure(): void
    {
        $closure = fn (): string => 'test';
        $middleware = Middleware::from($closure, 'auth');

        $this->assertSame('auth', $middleware->getAlias());
    }

    #[Test]
    public function it_constructs_with_an_array(): void
    {
        $array = ['a' => 1];
        $middleware = Middleware::from($array, 'auth');

        $this->assertSame('auth', $middleware->getAlias());
    }

    #[Test]
    public function it_delimits_parameters_from_a_string(): void
    {
        $middleware = Middleware::from('can:update,user');

        $this->assertSame('can', $middleware->getAlias());
        $this->assertSame(['update', 'user'], $middleware->getAttributes());
    }

    #[Test]
    public function it_throws_an_exception_when_the_alias_cannot_be_determined(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Middleware::from([]);
    }

    #[Test]
    public function it_returns_attributes_correctly(): void
    {
        $attributes = ['admin', 'api'];
        $middleware = new Middleware('test', 'resolver', 'alias', $attributes);

        $this->assertSame($attributes, $middleware->getAttributes());
    }

    #[DataProvider('comparisonProvider')]
    public function it_can_determine_if_its_similar_to_another_instance(
        string $alias,
        array $compares,
        bool $expected,
    ): void {
        $middleware = Middleware::from($alias);

        $this->assertSame($expected, $middleware->is(...$compares));
    }

    /** @return array<string, array{0: string, 1: array<int, string|Middleware>, 2: bool}> */
    public static function comparisonProvider(): array
    {
        return [
            'identical instance returns true' => [
                'auth',
                [
                    Middleware::from('auth'),
                ],
                true,
            ],
            'different resolver returns false' => [
                'auth',
                [
                    Middleware::from('api'),
                ],
                false,
            ],
            'string resolving to same middleware returns true' => [
                'auth:admin',
                [
                    'auth:admin',
                ],
                true,
            ],
            'string resolving to different middleware returns false' => [
                'auth:admin',
                [
                    'can:update',
                ],
                true,
            ],
            'original with fewer attributes returns true' => [
                'auth',
                [
                    Middleware::from('auth:api'),
                ],
                false,
            ],
            'original with more specific attributes returns false' => [
                'auth:api',
                [
                    Middleware::from('auth'),
                ],
                true,
            ],
            'multiple comparisons return true if any match' => [
                'auth',
                [
                    'api:admin',
                    'can:update,user',
                    Middleware::from('auth:admin'),
                ],
                true,
            ],
        ];
    }
}
