<?php

namespace Girgias\StubToDocbook\MetaData\Classes;

use Dom\Element;
use Dom\Text;
use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\Initializer;
use Girgias\StubToDocbook\MetaData\Visibility;
use Girgias\StubToDocbook\Types\DocumentedTypeParser;
use Girgias\StubToDocbook\Types\ReflectionTypeParser;
use Girgias\StubToDocbook\Types\Type;
use Roave\BetterReflection\Reflection\ReflectionProperty;

final class PropertyMetaData
{
    /**
     * @param list<AttributeMetaData> $attributes
     */
    public function __construct(
        readonly string $name,
        readonly Type|null $type,
        readonly Initializer|null $defaultValue = null,
        readonly Visibility $visibility = Visibility::Public,
        readonly array $attributes = [],
        readonly bool $isReadOnly = false,
        readonly bool $isStatic = false,
        readonly bool $isFinal = false,
        readonly bool $isDeprecated = false,
    ) {}


    /**
     * DocBook 5.2 <fieldsynopsis> documentation
     * URL: https://tdg.docbook.org/tdg/5.2/fieldsynopsis
     */
    public static function parseFromDoc(Element $element): self
    {
        if ($element->tagName !== 'fieldsynopsis') {
            throw new \Exception('Unexpected tag "' . $element->tagName . '"');
        }

        $name = null;
        $type = null;
        $defaultValue = null;
        $visibility = Visibility::Public;
        $isStatic = false;
        $isReadOnly = false;

        foreach ($element->childNodes as $node) {
            if ($node instanceof Text) {
                continue;
            }
            if (($node instanceof Element) === false) {
                throw new \Exception("Unexpected node type: " . $node::class);
            }
            match ($node->tagName) {
                'modifier' => match ($node->textContent) {
                    'public' => $visibility = Visibility::Public,
                    'protected' => $visibility = Visibility::Protected,
                    'private' => $visibility = Visibility::Private,
                    'static' => $isStatic = true,
                    'readonly' => $isReadOnly = true,
                    default => null,
                },
                'type' => $type = DocumentedTypeParser::parse($node),
                'varname' => $name = $node->textContent,
                'initializer' => $defaultValue = Initializer::parseFromDoc($node),
                default => throw new \Exception('Unexpected <fieldsynopsis> child tag: <' . $node->tagName . '>'),
            };
        }

        return new self(
            $name,
            $type,
            defaultValue: $defaultValue,
            visibility: $visibility,
            isReadOnly: $isReadOnly,
            isStatic: $isStatic,
        );
    }

    public static function fromReflectionData(ReflectionProperty $reflectionData): self
    {
        $name = $reflectionData->getName();
        $type = null;

        $attributes = array_map(
            AttributeMetaData::fromReflectionData(...),
            $reflectionData->getAttributes(),
        );

        $reflectionType = $reflectionData->getType();
        if ($reflectionType !== null) {
            $type = ReflectionTypeParser::convertFromReflectionType($reflectionData->getType());
        }

        $defaultValue = $reflectionData->getDefaultValueExpression();
        if ($defaultValue) {
            $defaultValue = Initializer::fromPhpParserExpr($defaultValue);
        }

        return new self(
            $name,
            $type,
            defaultValue: $defaultValue,
            visibility: Visibility::fromReflectionData($reflectionData),
            attributes: $attributes,
            isReadOnly: $reflectionData->isReadOnly(),
            isStatic: $reflectionData->isStatic(),
            isFinal: $reflectionData->isFinal(),
            isDeprecated: $reflectionData->isDeprecated(),
        );
    }
}
