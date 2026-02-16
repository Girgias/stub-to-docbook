<?php

namespace Girgias\StubToDocbook\Differ;

use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;

class FunctionListDiffer
{
    /**
     * @param array<string, FunctionMetaData> $fromStubs
     * @param array<string, FunctionMetaData> $fromDocs
     */
    public static function diff(array $fromStubs, array $fromDocs): FunctionListDiff
    {
        $missing = [];
        $mismatched = [];
        $valid = 0;

        foreach ($fromStubs as $name => $stubFunction) {
            if (!array_key_exists($name, $fromDocs)) {
                $missing[$name] = $stubFunction;
                continue;
            }
            if (!$stubFunction->isSame($fromDocs[$name])) {
                $mismatched[$name] = ['stub' => $stubFunction, 'doc' => $fromDocs[$name]];
            } else {
                $valid++;
            }
        }

        return new FunctionListDiff($valid, $missing, $mismatched);
    }
}
