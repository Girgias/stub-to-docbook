<?php

namespace Girgias\StubToDocbook\Differ;

final readonly class EnumDiff
{
    public function __construct(
        readonly string $enumName,
        readonly MemberListDiff $cases,
        readonly MemberListDiff $methods,
        readonly bool $backingTypeMismatch = false,
    ) {}

    public function hasDifferences(): bool
    {
        return $this->cases->missing !== []
            || $this->cases->extra !== []
            || $this->methods->missing !== []
            || $this->methods->extra !== []
            || $this->backingTypeMismatch;
    }
}
