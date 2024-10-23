<?php

namespace Girgias\StubToDocbook\Differ;

use Girgias\StubToDocbook\Stubs\StubConstantList;

final readonly class ConstantListDiff
{
    public function __construct(
        readonly StubConstantList $valid,
        readonly StubConstantList $incorrectType,
        readonly StubConstantList $missing,
    ) {}
}