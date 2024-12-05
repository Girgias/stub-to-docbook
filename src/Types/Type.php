<?php

namespace Girgias\StubToDocbook\Types;

use Girgias\StubToDocbook\FP\Equatable;

interface Type extends Equatable
{
    /**
     * @param Type $other
     */
    public function isSame(mixed $other): bool;
    public function toXml(): string;
}
