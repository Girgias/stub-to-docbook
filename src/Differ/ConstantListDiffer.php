<?php

namespace Girgias\StubToDocbook\Differ;

use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Lists\ConstantList;

class ConstantListDiffer
{
    public static function diff(ConstantList $fromStubs, ConstantList $fromDocs): ConstantListDiff
    {
        $totalStubConstants = count($fromStubs);
        $docConstants = $fromDocs->constants;
        $missingDocs = [];
        $incorrectTypes = [];
        $incorrectIdForLinking = [];

        foreach ($fromStubs->constants as $name => $constant) {
            if (!array_key_exists($name, $docConstants)) {
                $missingDocs[$name] = $constant;
                continue;
            }
            if ($docConstants[$name]->type === null) {
                $incorrectTypes[$name] = [$constant, 'MISSING'];
            } else {
                if (!$docConstants[$name]->type->isSame($constant->type)) {
                    $type = $docConstants[$name]->type->name;
                    $incorrectTypes[$name] = [$constant, $type];
                }
            }
            if ($constant->id !== $docConstants[$name]->id) {
                $incorrectIdForLinking[$name] = $docConstants[$name];
            }
        }

        return new ConstantListDiff(
            $totalStubConstants - count($incorrectTypes) - count($missingDocs) - count($incorrectIdForLinking),
            $incorrectTypes,
            new ConstantList($missingDocs),
            new ConstantList($incorrectIdForLinking),
        );
    }

    /**
     * @param array<string, ConstantMetaData> $baseVersion
     * @param array<string, ConstantMetaData> $newVersion
     */
    public static function stubDiff(array $baseVersion, array $newVersion): ConstantStubListDiff
    {
        $newConstants = array_diff_key($newVersion, $baseVersion);

        $removedConstants = array_diff_key($baseVersion, $newVersion);

        $baseDeprecated = array_filter($baseVersion, symbol_is_deprecated(...));
        $newDeprecated = array_filter($newVersion, symbol_is_deprecated(...));
        $deprecatedConstants = array_diff_key($newDeprecated, $baseDeprecated);

        return new ConstantStubListDiff(
            new ConstantList($newConstants),
            new ConstantList($removedConstants),
            new ConstantList($deprecatedConstants),
        );
    }
}
