<?php

namespace Girgias\StubToDocbook\Differ;

use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Lists\ConstantList;

final readonly class ConstantListDiff
{
    /** @param array<string, array{0: ConstantMetaData, 1: string}> $incorrectTypes */
    public function __construct(
        readonly int $valid,
        readonly array $incorrectTypes,
        readonly ConstantList $missing,
        readonly ConstantList $incorrectIdForLinking,
    ) {}
}
