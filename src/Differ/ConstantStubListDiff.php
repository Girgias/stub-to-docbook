<?php

namespace Girgias\StubToDocbook\Differ;

use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Lists\ConstantList;

final readonly class ConstantStubListDiff
{
    public function __construct(
        readonly ConstantList $new,
        readonly ConstantList $removed,
        readonly ConstantList $deprecated,
    ) {}
}
