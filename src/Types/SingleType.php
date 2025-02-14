<?php

namespace Girgias\StubToDocbook\Types;

final class SingleType implements Type
{
    public function __construct(public readonly string $name) {}

    /**
     * @param Type $other
     */
    public function isSame(mixed $other): bool
    {
        if ($this::class !== $other::class) {
            return false;
        }
        return $this->name === $other->name;
    }

    public function toXml(): string
    {
        return '<type>' . $this->name . '</type>';
    }
}
