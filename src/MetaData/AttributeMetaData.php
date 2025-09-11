<?php

namespace Girgias\StubToDocbook\MetaData;

use Dom\Element;
use Girgias\StubToDocbook\FP\Equatable;

final readonly class AttributeMetaData implements Equatable
{
    /**
     * @param array<string, Initializer> $arguments
     */
    public function __construct(
        readonly string $name,
        readonly array $arguments = [],
    ) {}

    public function isSame(mixed $other): bool
    {
        $diff = array_udiff_assoc($this->arguments, $other->arguments,
                fn (Initializer $l, Initializer $r) => (int)!$r->isSame($l));
        return $this->name === $other->name && $diff === [];
    }

    public static function parseFromDoc(Element $element): AttributeMetaData
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
        return self::parseFromString($attribute);
    }

    private static function parseFromString(string $string): self
    {
        $name = '';
        $arguments = [];
        $key = '';
        $buffer = '';
        for ($i = 0; $i < strlen($string); ++$i) {
            if ($string[$i] === '(') {
                $name = $buffer;
                $buffer = '';
                continue;
            }
            if ($string[$i] === ':') {
                $key = trim($buffer);
                $buffer = '';
                continue;
            }
            if ($string[$i] === ',' || $string[$i] === ')') {
                $arguments[$key] = new Initializer(InitializerVariant::Literal, trim($buffer));
                $buffer = '';
                continue;
            }
            $buffer .= $string[$i];
        }
        if ($name === '') {
            $name = $buffer;
        }

        return new self($name, $arguments);
    }
}
