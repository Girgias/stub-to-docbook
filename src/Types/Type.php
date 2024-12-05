<?php

namespace Girgias\StubToDocbook\Types;

interface Type
{
    public function isSame(Type $type): bool;
    public function toXml(): string;
}
