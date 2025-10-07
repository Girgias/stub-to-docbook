<?php

namespace Girgias\StubToDocbook\Differ;

use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;

/**
 * @template T of FunctionMetaData|ConstantMetaData
 */
final class SymbolListDiffer
{
    /**
     * @param array<string, T> $baseVersion
     * @param array<string, T> $newVersion
     * TODO: Function Signature differences between existing functions
     * @return SymbolStubMapDiff<T>
     */
    public static function stubDiff(array $baseVersion, array $newVersion): SymbolStubMapDiff
    {
        $newSymbols = array_diff_key($newVersion, $baseVersion);

        $removedSymbols = array_diff_key($baseVersion, $newVersion);

        $baseDeprecated = array_filter($baseVersion, symbol_is_deprecated(...));
        $newDeprecated = array_filter($newVersion, symbol_is_deprecated(...));
        $newDeprecationsSymbols = array_diff_key($newDeprecated, $baseDeprecated);


        return new SymbolStubMapDiff(
            $newSymbols,
            $removedSymbols,
            $newDeprecationsSymbols,
        );
    }
}
