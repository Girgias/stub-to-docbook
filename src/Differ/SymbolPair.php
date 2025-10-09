<?php

namespace Girgias\StubToDocbook\Differ;

/**
 * @template T
 */
final class SymbolPair
{
    public function __construct(
       readonly mixed $existing,
       readonly mixed $new
    ) {}
}