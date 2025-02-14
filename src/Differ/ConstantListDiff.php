<?php

namespace Girgias\StubToDocbook\Differ;

use Girgias\StubToDocbook\Documentation\DocumentedConstantList;
use Girgias\StubToDocbook\Stubs\StubConstant;
use Girgias\StubToDocbook\Stubs\StubConstantList;

final readonly class ConstantListDiff
{
    /** @param array<string, array{0: StubConstant, 1: string}> $incorrectTypes */
    public function __construct(
        readonly int $valid,
        readonly array $incorrectTypes,
        readonly StubConstantList $missing,
        readonly DocumentedConstantList $incorrectIdForLinking,
    ) {}
}
