<?php

namespace Girgias\StubToDocbook\Reports;

final readonly class ExtensionCoverage
{
    public readonly int $missing;

    public function __construct(
        readonly string $name,
        readonly int $total,
        readonly int $documented,
    ) {
        $this->missing = $total - $documented;
    }

    public function coveragePercentage(): float
    {
        if ($this->total === 0) {
            return 100.0;
        }
        return round(($this->documented / $this->total) * 100, 2);
    }
}
