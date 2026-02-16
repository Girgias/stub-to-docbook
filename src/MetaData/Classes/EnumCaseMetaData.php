<?php

namespace Girgias\StubToDocbook\MetaData\Classes;

use Dom\Element;
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
        readonly ?Initializer $value = null,
        readonly array $attributes = [],
        readonly bool $isDeprecated = false,
    ) {}

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

    /**
     * Parse an enum case from a DocBook <fieldsynopsis> element.
     */
    public static function parseFromDoc(Element $element): self
    {
        $varnameTags = $element->getElementsByTagName('varname');
        assert($varnameTags->length === 1);
        $name = $varnameTags[0]->textContent;

        $value = null;
        $initializerTags = $element->getElementsByTagName('initializer');
        if ($initializerTags->length === 1) {
            $value = new Initializer(
                InitializerVariant::Literal,
                trim($initializerTags[0]->textContent),
            );
        }

        return new self($name, $value);
    }
}
