<?php

namespace Girgias\StubToDocbook\Differ;

use Girgias\StubToDocbook\Documentation\DocumentedConstant;
use Girgias\StubToDocbook\Documentation\DocumentedConstantList;
use Girgias\StubToDocbook\Stubs\StubConstant;
use Girgias\StubToDocbook\Stubs\StubConstantList;

class ConstantListDiffer
{
    public static function diff(StubConstantList $fromStubs, DocumentedConstantList $fromDocs): ConstantListDiff
    {
        $mapDocConst = array_combine(
            array_map(
                fn (DocumentedConstant $c) => $c->name,
                $fromDocs->constants
            ),
            $fromDocs->constants
        );
        $mapStubConst = array_combine(
            array_map(
                fn (StubConstant $c) => $c->name,
                $fromStubs->constants
            ),
            $fromStubs->constants
        );
        $missingConsts = array_diff_key(
            $mapStubConst,
            $mapDocConst,
        );
        $inDocs = array_diff_key(
            $mapStubConst,
            $missingConsts,
        );
        $validTypes = array_uintersect_assoc(
            $inDocs,
            $mapDocConst,
            fn (StubConstant|DocumentedConstant $a, StubConstant|DocumentedConstant $b) => strcmp($a->type, $b->type),
        );

        $valid = StubConstantList::fromArrayOfStubConstants($validTypes);
        if (count($validTypes) === count($inDocs)) {
            $incorrectType = StubConstantList::fromArrayOfStubConstants([]);
        } else {
            $incorrectType = StubConstantList::fromArrayOfStubConstants(array_diff_key(
                $inDocs,
                $validTypes,
            ));
        }

        return new ConstantListDiff(
            $valid,
            $incorrectType,
            StubConstantList::fromArrayOfStubConstants($missingConsts),
        );
    }
}