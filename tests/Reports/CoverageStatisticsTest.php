<?php

namespace Reports;

use Girgias\StubToDocbook\Reports\CoverageStatistics;
use Girgias\StubToDocbook\Reports\ExtensionCoverage;
use PHPUnit\Framework\TestCase;

class CoverageStatisticsTest extends TestCase
{
    public function test_coverage_percentage(): void
    {
        $stats = new CoverageStatistics(100, 75, 25, []);
        self::assertSame(75.0, $stats->coveragePercentage());
    }

    public function test_empty_coverage(): void
    {
        $stats = new CoverageStatistics(0, 0, 0, []);
        self::assertSame(100.0, $stats->coveragePercentage());
    }

    public function test_from_extension_counts(): void
    {
        $stubs = ['core' => 50, 'json' => 10, 'curl' => 30];
        $docs = ['core' => 45, 'json' => 10];

        $stats = CoverageStatistics::fromExtensionCounts($stubs, $docs);

        self::assertSame(90, $stats->totalSymbols);
        self::assertSame(55, $stats->documentedSymbols);
        self::assertSame(35, $stats->missingSymbols);
        self::assertCount(3, $stats->extensions);

        // Extensions should be sorted alphabetically
        $extNames = array_keys($stats->extensions);
        self::assertSame(['core', 'curl', 'json'], $extNames);

        // Core: 45/50
        self::assertSame(90.0, $stats->extensions['core']->coveragePercentage());
        // Json: 10/10
        self::assertSame(100.0, $stats->extensions['json']->coveragePercentage());
        // Curl: 0/30
        self::assertSame(0.0, $stats->extensions['curl']->coveragePercentage());
    }

    public function test_json_output(): void
    {
        $stats = CoverageStatistics::fromExtensionCounts(
            ['test' => 10],
            ['test' => 8],
        );

        $json = $stats->toJson();
        $data = json_decode($json, true);

        self::assertSame(10, $data['total_symbols']);
        self::assertSame(8, $data['documented_symbols']);
        self::assertSame(2, $data['missing_symbols']);
        self::assertEquals(80.0, $data['coverage_percentage']);
        self::assertArrayHasKey('test', $data['extensions']);
        self::assertArrayHasKey('generated_at', $data);
    }

    public function test_extension_coverage(): void
    {
        $ext = new ExtensionCoverage('test', 20, 15);
        self::assertSame(5, $ext->missing);
        self::assertSame(75.0, $ext->coveragePercentage());
    }
}
