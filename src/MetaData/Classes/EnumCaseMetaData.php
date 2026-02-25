<?php

namespace Girgias\StubToDocbook\MetaData\Classes;

use Dom\Element;
use Dom\Text;
use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\Initializer;
use Girgias\StubToDocbook\MetaData\InitializerVariant;
use Roave\BetterReflection\Reflection\ReflectionEnumCase;

final class EnumCaseMetaData
{
    /**
     * @param list<AttributeMetaData> $attributes
     */
    public function __construct(
        readonly string $name,
        readonly Initializer|null $value = null,
        readonly array $attributes = [],
        readonly bool $isDeprecated = false,
    ) {}

    /**
     * DocBook 5.2 <enumitem> documentation
     * URL: https://tdg.docbook.org/tdg/5.2/enumitem
     */
    public static function parseFromDoc(Element $element): self
    {
        if ($element->tagName !== 'enumitem') {
            throw new \Exception('Unexpected tag "' . $element->tagName . '"');
        }

        $name = null;
        $value = null;

        foreach ($element->childNodes as $node) {
            if ($node instanceof Text) {
                continue;
            }
            if (($node instanceof Element) === false) {
                throw new \Exception("Unexpected node type: " . $node::class);
            }
            /**
             * enumitem ::=
             *   Sequence of:
             *      enumidentifier
             *          Zero or more of:
             *      enumvalue
             *      enumitemdescription?
             *
             * @var 'enumidentifier'|'enumvalue'|'enumitemdescription' $tagName
             */
            $tagName = $node->tagName;
            match ($tagName) {
                'enumidentifier' => $name = $node->textContent,
                'enumvalue' => $value = new Initializer(InitializerVariant::Literal, $node->textContent),
                'enumitemdescription' => null,
            };
        }

        return new self($name, $value);
    }

    public static function fromReflectionData(ReflectionEnumCase $reflectionData): self
    {
        $value = null;
        if ($reflectionData->hasValueExpression()) {
            $value = Initializer::fromPhpParserExpr($reflectionData->getValueExpression());
        }

        $attributes = array_map(
            AttributeMetaData::fromReflectionData(...),
            $reflectionData->getAttributes(),
        );

        return new self(
            $reflectionData->getName(),
            $value,
            attributes: $attributes,
            isDeprecated: $reflectionData->isDeprecated(),
        );
    }
}
