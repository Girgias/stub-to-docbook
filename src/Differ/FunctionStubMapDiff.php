<?php

namespace Girgias\StubToDocbook\Differ;

use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;

final class FunctionStubMapDiff
{
    /**
     * @param array<string, FunctionMetaData> $new
     * @param array<string, FunctionMetaData> $removed
     * @param array<string, FunctionMetaData> $deprecated
     */
    public function __construct(
        readonly array $new,
        readonly array $removed,
        readonly array $deprecated,
    ) {}
}
