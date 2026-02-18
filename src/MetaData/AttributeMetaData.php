<?php

namespace Girgias\StubToDocbook\MetaData;

use Dom\Element;
use Dom\XMLDocument;
use Girgias\StubToDocbook\FP\Equatable;
use Roave\BetterReflection\Reflection\ReflectionAttribute;

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
        $diff = array_udiff_assoc(
            $this->arguments,
            $other->arguments,
            fn (Initializer $l, Initializer $r) => (int) !$r->isSame($l),
        );
        return $this->name === $other->name && $diff === [];
    }

    /**
     * DocBook 5.2 <modifier role="attribute"> generation
     */
    public function toModifierXml(XMLDocument $document): Element
    {
        /* TODO: handle attribute parameters after XML markup has been determined */
        $modifier = $document->createElement('modifier');
        $modifier->setAttribute('role', 'attribute');
        $modifier->textContent = '#[' . $this->name . ']';
        return $modifier;
    }

    public static function fromReflectionData(ReflectionAttribute $reflectionData): self
    {
        /* getName() returns the qualified name rather than the FQN */
        return new self(
            '\\' . $reflectionData->getName(),
            /* @phpstan-ignore argument.type (as we don't have positional attribute arguments) */
            array_map(
                Initializer::fromPhpParserExpr(...),
                $reflectionData->getArgumentsExpressions(),
            ),
        );
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
