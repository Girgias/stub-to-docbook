<?php

namespace Girgias\StubToDocbook\Differ;

use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;

/**
 * @template T of FunctionMetaData|ConstantMetaData
 */
final class SymbolStubMapDiff
{
    /**
     * @param array<string, T> $new
     * @param array<string, T> $removed
     * @param array<string, T> $deprecated
     */
    public function __construct(
        readonly array $new,
        readonly array $removed,
        readonly array $deprecated,
    ) {}
}
