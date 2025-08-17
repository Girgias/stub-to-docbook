<?php

namespace Girgias\StubToDocbook\Differ;

use Girgias\StubToDocbook\Documentation\DocumentedConstantList;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\Stubs\StubConstantList;

final readonly class ConstantListDiff
{
    /** @param array<string, array{0: ConstantMetaData, 1: string}> $incorrectTypes */
    public function __construct(
        readonly int $valid,
        readonly array $incorrectTypes,
        readonly StubConstantList $missing,
        readonly DocumentedConstantList $incorrectIdForLinking,
    ) {}
}
