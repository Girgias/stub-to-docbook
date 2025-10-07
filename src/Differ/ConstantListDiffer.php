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
}
