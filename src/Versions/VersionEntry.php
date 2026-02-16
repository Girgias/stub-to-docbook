<?php

namespace Girgias\StubToDocbook\Versions;

final readonly class VersionEntry
{
    public function __construct(
        readonly string $name,
        readonly string $from,
    ) {}
}
