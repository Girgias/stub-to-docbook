<?php

namespace Girgias\StubToDocbook\Documentation;

use Dom\Element;
use Girgias\StubToDocbook\FP\Equatable;

final readonly class DocumentedAttribute implements Equatable
{
    public function __construct(
        readonly string $name,
        //readonly array $arguments = [],
    ) {}

    public function isSame(mixed $other): bool
    {
        return $this->name === $other->name;
    }

    public static function parseFromDoc(Element $element): DocumentedAttribute
    {
        if (!$element->hasAttributes()) {
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
