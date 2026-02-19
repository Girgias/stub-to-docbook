<?php

namespace Girgias\StubToDocbook\Differ;

final readonly class MemberListDiff
{
    /**
     * @param list<string> $missing Members in stub but not in doc
     * @param list<string> $extra Members in doc but not in stub
     * @param list<string> $matching Members present in both
     */
    public function __construct(
        readonly array $missing,
        readonly array $extra,
        readonly array $matching,
    ) {}
}
