<?php

namespace Girgias\StubToDocbook\Differ;

use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;

final readonly class FunctionListDiff
{
    /**
     * @param array<string, FunctionMetaData> $missing Functions in stubs but not in docs
     * @param array<string, array{stub: FunctionMetaData, doc: FunctionMetaData}> $mismatched Functions with different signatures
     */
    public function __construct(
        readonly int $valid,
        readonly array $missing,
        readonly array $mismatched,
    ) {}
}
