<?php

namespace Girgias\StubToDocbook\Reports;

final readonly class CoverageStatistics
{
    /**
     * @param array<string, ExtensionCoverage> $extensions
     */
    public function __construct(
        readonly int $totalSymbols,
        readonly int $documentedSymbols,
        readonly int $missingSymbols,
        readonly array $extensions,
    ) {}

    public function coveragePercentage(): float
    {
        if ($this->totalSymbols === 0) {
            return 100.0;
        }
        return round(($this->documentedSymbols / $this->totalSymbols) * 100, 2);
    }

    /**
     * Generate a JSON report string.
     */
    public function toJson(): string
    {
        $data = [
            'generated_at' => date('Y-m-d H:i:s'),
            'total_symbols' => $this->totalSymbols,
            'documented_symbols' => $this->documentedSymbols,
            'missing_symbols' => $this->missingSymbols,
            'coverage_percentage' => $this->coveragePercentage(),
            'extensions' => [],
        ];

        foreach ($this->extensions as $name => $ext) {
            $data['extensions'][$name] = [
                'total' => $ext->total,
                'documented' => $ext->documented,
                'missing' => $ext->missing,
                'coverage_percentage' => $ext->coveragePercentage(),
            ];
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * Build coverage statistics from stub and doc symbol counts grouped by extension.
     *
     * @param array<string, int> $stubCountByExtension Total symbols per extension from stubs
     * @param array<string, int> $docCountByExtension Documented symbols per extension from docs
     */
    public static function fromExtensionCounts(array $stubCountByExtension, array $docCountByExtension): self
    {
        $extensions = [];
        $totalSymbols = 0;
        $documentedSymbols = 0;

        foreach ($stubCountByExtension as $ext => $total) {
            $documented = $docCountByExtension[$ext] ?? 0;
            $extensions[$ext] = new ExtensionCoverage($ext, $total, $documented);
            $totalSymbols += $total;
            $documentedSymbols += $documented;
        }

        ksort($extensions);

        return new self(
            $totalSymbols,
            $documentedSymbols,
            $totalSymbols - $documentedSymbols,
            $extensions,
        );
    }
}
