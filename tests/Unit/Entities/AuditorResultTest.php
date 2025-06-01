<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Entities;

use MyDev\AuditRoutes\Contracts\AuditorInterface;
use MyDev\AuditRoutes\Entities\AuditorResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class AuditorResultTest extends TestCase
{
    #[Test]
    public function it_constructs_with_auditor_and_result(): void
    {
        $auditor = $this->mockAuditor();
        $auditorResult = new AuditorResult($auditor, 42);

        $this->assertInstanceOf(AuditorResult::class, $auditorResult);
    }

    #[Test]
    public function it_returns_the_expected_array_via_to_array(): void
    {
        $auditor = $this->mockAuditor();
        $auditorResult = new AuditorResult($auditor, 99);

        $array = $auditorResult->toArray();

        $this->assertSame($auditor, $array['auditor']);
        $this->assertSame(99, $array['result']);
    }

    #[Test]
    public function it_serializes_to_json_with_expected_structure(): void
    {
        $auditor = $this->mockAuditor();
        $auditorResult = new AuditorResult($auditor, 7);

        $json = json_encode($auditorResult);

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('auditor', $decoded);
        $this->assertArrayHasKey('result', $decoded);
        $this->assertSame(7, $decoded['result']);
        $this->assertIsArray($decoded['auditor']);
    }

    #[Test]
    public function it_returns_same_output_for_to_array_and_json_serialize(): void
    {
        $auditor = $this->mockAuditor();

        $auditorResult = new AuditorResult($auditor, 11);

        $this->assertSame($auditorResult->toArray(), $auditorResult->jsonSerialize());
    }

    #[Test]
    public function it_preserves_auditor_instance_identity(): void
    {
        $auditor = $this->mockAuditor();

        $auditorResult = new AuditorResult($auditor, 5);

        $this->assertSame($auditor, $auditorResult->toArray()['auditor']);
    }

    /**
     * @param string $name
     * @return AuditorInterface
     */
    private function mockAuditor(string $name = 'default'): AuditorInterface
    {
        try {
            $auditor = $this->createMock(AuditorInterface::class);
        } catch (Exception) {
            $this->fail('Failed to create auditor mock.');
        }

        $auditor->method('jsonSerialize')->willReturn(['name' => $name]);

        return $auditor;
    }
}
