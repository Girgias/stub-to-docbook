<?php

namespace Girgias\StubToDocbook\MetaData\Classes;

use Dom\Element;
use Dom\XPath;
use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;
use Girgias\StubToDocbook\Types\DocumentedTypeParser;
use Girgias\StubToDocbook\Types\ReflectionTypeParser;
use Girgias\StubToDocbook\Types\Type;
use Roave\BetterReflection\Reflection\ReflectionEnum;

final class EnumMetaData
{
    /**
     * @param list<EnumCaseMetaData> $cases
     * @param list<FunctionMetaData> $methods
     * @param list<string> $implements
     * @param list<AttributeMetaData> $attributes
     */
    public function __construct(
        readonly string $name,
        readonly ?Type $backingType,
        readonly array $cases,
        readonly array $methods,
        readonly string $extension,
        readonly array $implements = [],
        readonly array $attributes = [],
        readonly bool $isDeprecated = false,
    ) {}

    public static function fromReflectionData(ReflectionEnum $reflectionData): self
    {
        $backingType = null;
        if ($reflectionData->isBacked()) {
            $backingType = ReflectionTypeParser::convertFromReflectionType($reflectionData->getBackingType());
        }

        $cases = array_map(
            EnumCaseMetaData::fromReflectionData(...),
            $reflectionData->getCases(),
        );

        $methods = array_values(array_filter(
            array_map(
                FunctionMetaData::fromReflectionData(...),
                $reflectionData->getMethods(),
            ),
            fn (FunctionMetaData $m) => !in_array($m->name, ['cases', 'from', 'tryFrom'], true),
        ));

        $implements = array_map(
            fn ($interface) => $interface->getName(),
            $reflectionData->getInterfaces(),
        );

        $attributes = array_map(
            AttributeMetaData::fromReflectionData(...),
            $reflectionData->getAttributes(),
        );

        return new self(
            $reflectionData->getName(),
            $backingType,
            $cases,
            $methods,
            extension: $reflectionData->getExtensionName(),
            implements: array_values($implements),
            attributes: $attributes,
            isDeprecated: $reflectionData->isDeprecated(),
        );
    }

    /**
     * Parse an enum from a DocBook <classsynopsis class="enum"> element.
     */
    public static function parseFromDoc(Element $element, string $extension): self
    {
        $doc = $element->ownerDocument;
        $xpath = new XPath($doc);
        $xpath->registerNamespace('db', 'http://docbook.org/ns/docbook');

        // Parse enum name
        $enumNameTags = $xpath->query('.//db:enumname', $element);
        if ($enumNameTags->length === 0) {
            // Fallback to classname
            $classNameTags = $xpath->query('.//db:classname', $element);
            assert($classNameTags->length >= 1);
            $name = $classNameTags[0]->textContent;
        } else {
            $name = $enumNameTags[0]->textContent;
        }

        // Parse backing type
        $backingType = null;
        $typeTags = $xpath->query('.//db:classsynopsisinfo//db:type', $element);
        if ($typeTags->length > 0) {
            $backingType = DocumentedTypeParser::parse($typeTags[0]);
        }

        // Parse implemented interfaces
        $implements = [];
        $interfaceTags = $xpath->query('.//db:oointerface/db:interfacename', $element);
        foreach ($interfaceTags as $interfaceTag) {
            $implements[] = $interfaceTag->textContent;
        }

        // Parse enum cases from fieldsynopsis
        $cases = [];
        $fieldSynopsisTags = $xpath->query('.//db:fieldsynopsis', $element);
        foreach ($fieldSynopsisTags as $fieldTag) {
            $cases[] = EnumCaseMetaData::parseFromDoc($fieldTag);
        }

        // Parse methods from methodsynopsis
        $methods = [];
        $methodSynopsisTags = $xpath->query('.//db:methodsynopsis', $element);
        foreach ($methodSynopsisTags as $methodTag) {
            $methods[] = FunctionMetaData::parseFromDoc($methodTag, $extension);
        }

        return new self(
            $name,
            $backingType,
            $cases,
            $methods,
            extension: $extension,
            implements: $implements,
        );
    }
}
