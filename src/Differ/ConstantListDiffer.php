<?php

namespace Girgias\StubToDocbook\Differ;

use Girgias\StubToDocbook\Documentation\DocumentedConstantList;
use Girgias\StubToDocbook\Documentation\DocumentedConstantListType;
use Girgias\StubToDocbook\Stubs\StubConstantList;
use Girgias\StubToDocbook\Types\SingleType;

class ConstantListDiffer
{
    public static function diff(StubConstantList $fromStubs, DocumentedConstantList $fromDocs): ConstantListDiff
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
            if ($docConstants[$name]->type instanceof SingleType) {
                if (!$docConstants[$name]->type->isSame($constant->type)) {
                    $type = $docConstants[$name]->type->name;
                    $incorrectTypes[$name] = [$constant, $type];
                }
            } else {
                $incorrectTypes[$name] = [$constant, 'MISSING'];
            }
            if (!$docConstants[$name]->hasCorrectIdForLinking()) {
                $incorrectIdForLinking[$name] = $docConstants[$name];
            }
        }

        return new ConstantListDiff(
            $totalStubConstants - count($incorrectTypes) - count($missingDocs) - count($incorrectIdForLinking),
            $incorrectTypes,
            StubConstantList::fromArrayOfStubConstants($missingDocs),
            new DocumentedConstantList(DocumentedConstantListType::VarEntryList, $incorrectIdForLinking),
        );
    }
}
