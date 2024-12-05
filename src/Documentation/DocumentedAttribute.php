<?php

namespace Girgias\StubToDocbook\Documentation;

final readonly class DocumentedAttribute
{
    public function __construct(
        readonly string $name,
        //readonly array $arguments = [],
    ) {}

    public static function parseFromDoc(\DOMElement $element): DocumentedAttribute
    {
        if ($element->attributes === null) {
            throw new \Exception("No attributes");
        }
        $role = $element->attributes['role'];
        if ($role === null) {
            throw new \Exception('No "role" attributes');
        }
        if ($role->value !== 'attribute') {
            throw new \Exception("Unexpected attribute role: " . $role->value);
        }
        $fullAttribute = $element->textContent;
        /* Skip initial "#[" and do not include the trailing "]# */
        $attribute = substr($fullAttribute, 2, strlen($fullAttribute) - 3);
        return new DocumentedAttribute($attribute);
    }
}
