<?php

namespace Girgias\StubToDocbook\Differ;

final readonly class ClassDiff
{
    public function __construct(
        readonly string $className,
        readonly MemberListDiff $constants,
        readonly MemberListDiff $properties,
        readonly MemberListDiff $methods,
    ) {}

    public function hasDifferences(): bool
    {
        return $this->constants->missing !== []
            || $this->constants->extra !== []
            || $this->properties->missing !== []
            || $this->properties->extra !== []
            || $this->methods->missing !== []
            || $this->methods->extra !== [];
    }
}
