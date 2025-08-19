<?php

namespace Girgias\StubToDocbook\Differ;

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

    public static function stubDiff(ConstantList $baseVersion, ConstantList $newVersion): ConstantStubListDiff
    {
        $newConstants = [];
        $removedConstants = [];
        $deprecatedConstants = [];

        $newNames = array_diff_key($newVersion->constants, $baseVersion->constants);
        foreach ($newNames as $name => $_) {
            $newConstants[$name] = $newVersion->constants[$name];
        }

        $removedNames = array_diff_key($baseVersion->constants, $newVersion->constants);
        foreach ($removedNames as $name => $_) {
            $removedConstants[$name] = $baseVersion->constants[$name];
        }

        return new ConstantStubListDiff(
            new ConstantList($newConstants),
            new ConstantList($removedConstants),
            new ConstantList($deprecatedConstants),
        );
    }
}
