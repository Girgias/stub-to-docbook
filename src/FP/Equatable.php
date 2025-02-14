<?php

namespace Girgias\StubToDocbook\FP;

interface Equatable
{
    public function isSame(mixed $other): bool;
}
