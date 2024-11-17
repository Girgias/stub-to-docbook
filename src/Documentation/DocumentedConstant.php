<?php

namespace Girgias\StubToDocbook\Documentation;

final readonly class DocumentedConstant
{
    public function __construct(
        readonly string $name,
        readonly string $type,
        readonly \DOMNode $description,
        readonly string|null $id = null
    ) {}

    public function hasCorrectIdForLinking(): bool
    {
        if ($this->id === null) {
            return false;
        }
        $correctId = 'constant.' . $this::xmlifyName($this->name);
        return $correctId === $this->id;
    }

    public static function xmlifyName(string $label): string
    {
        return str_replace('_', '-', strtolower($label));
    }
}