<?php

declare(strict_types=1);

namespace Surqlize\Tests\Unit\Benchmark;

use Surqlize\Benchmark\MemoryFootprintRunner;
use Surqlize\Model\ModelMetadata;
use Surqlize\Tests\TestCase;

final class MemoryFootprintRunnerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ModelMetadata::clear();
    }

    protected function tearDown(): void
    {
        ModelMetadata::clear();

        parent::tearDown();
    }

    public function test_memory_footprint_report_contains_stable_scenarios(): void
    {
        $report = (new MemoryFootprintRunner(iterations: 1))->run()->jsonSerialize();

        $this->assertSame('surqlize/surqlize', $report['metadata']['library']);
        $this->assertSame(1, $report['metadata']['iterations']);
        $this->assertArrayHasKey('usage_bytes', $report['baseline']);
        $this->assertArrayHasKey('real_usage_bytes', $report['baseline']);

        $scenarioNames = array_column($report['scenarios'], 'name');

        $this->assertContains('metadata_resolution', $scenarioNames);
        $this->assertContains('typed_query_compile', $scenarioNames);
		$this->assertContains('fieldset_resolution', $scenarioNames);
		$this->assertContains('generated_field_adapter_compile', $scenarioNames);
        $this->assertContains('edge_graph_compile', $scenarioNames);
        $this->assertContains('edge_endpoint_compile', $scenarioNames);
        $this->assertContains('hydration', $scenarioNames);
		$this->assertContains('hydration_bulk', $scenarioNames);
        $this->assertContains('field_generation', $scenarioNames);
        $this->assertContains('record_id_filters', $scenarioNames);

        foreach ($report['scenarios'] as $scenario) {
            $this->assertArrayHasKey('usage_delta_bytes', $scenario);
            $this->assertArrayHasKey('real_usage_delta_bytes', $scenario);
            $this->assertArrayHasKey('peak_delta_bytes', $scenario);
            $this->assertArrayHasKey('duration_ms', $scenario);
        }
    }

    public function test_memory_footprint_report_can_be_encoded_as_json(): void
    {
        $json = (new MemoryFootprintRunner(iterations: 1))->run()->toJson();
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('metadata', $decoded);
        $this->assertArrayHasKey('baseline', $decoded);
        $this->assertArrayHasKey('scenarios', $decoded);
    }
}
