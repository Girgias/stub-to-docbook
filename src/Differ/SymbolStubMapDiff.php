<?php

namespace Girgias\StubToDocbook\Differ;

/**
 * @template T of object{name: string, isDeprecated: bool}
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
