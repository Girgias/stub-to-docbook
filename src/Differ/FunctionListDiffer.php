<?php

namespace Girgias\StubToDocbook\Differ;

use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;

final class FunctionListDiffer
{
    /**
     * @param array<string, FunctionMetaData> $baseVersion
     * @param array<string, FunctionMetaData> $newVersion
     * TODO: Function Signature differences between existing functions
     */
    public static function stubDiff(array $baseVersion, array $newVersion): FunctionStubMapDiff
    {
        $newSymbols = array_diff_key($newVersion, $baseVersion);

        $removedSymbols = array_diff_key($baseVersion, $newVersion);

        $baseDeprecated = array_filter($baseVersion, symbol_is_deprecated(...));
        $newDeprecated = array_filter($newVersion, symbol_is_deprecated(...));
        $newDeprecationsSymbols = array_diff_key($newDeprecated, $baseDeprecated);


        return new FunctionStubMapDiff(
            $newSymbols,
            $removedSymbols,
            $newDeprecationsSymbols,
        );
    }
}
