<?php

namespace Girgias\StubToDocbook\Documentation;

final readonly class DocumentedConstant
{
    public function __construct(
        readonly string $name,
        readonly string $type,
        readonly \DOMNode $description,
    ) {}
}