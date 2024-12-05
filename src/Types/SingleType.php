<?php

namespace Girgias\StubToDocbook\Types;

final class SingleType implements Type
{
    public function __construct(public readonly string $name) { }

    public function isSame(Type $type): bool
    {
        if ($this::class !== $type::class) {
            return false;
        }
        return $this->name === $type->name;
    }

    public function toXml(): string
    {
        return '<type>' . $this->name . '</type>';
    }
}
