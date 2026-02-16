<?php

namespace Girgias\StubToDocbook\MetaData\Classes;

use Dom\Element;
use Dom\XPath;
use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;
use Girgias\StubToDocbook\MetaData\Visibility;
use Girgias\StubToDocbook\Types\DocumentedTypeParser;
use Girgias\StubToDocbook\Types\SingleType;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class ClassMetaData
{
    /**
     * @param list<PropertyMetaData> $properties
     * @param list<FunctionMetaData> $methods
     * @param list<ConstantMetaData> $constants
     * @param list<string> $implements
     * @param list<AttributeMetaData> $attributes
     */
    public function __construct(
        readonly string $name,
        readonly ?string $extends,
        readonly array $properties,
        readonly array $methods,
        readonly array $constants,
        readonly string $extension,
        readonly array $implements = [],
        readonly array $attributes = [],
        readonly bool $isFinal = false,
        readonly bool $isAbstract = false,
        readonly bool $isReadOnly = false,
        readonly bool $isDeprecated = false,
    ) {}

    public static function fromReflectionData(ReflectionClass $reflectionData): self
    {
        $properties = array_map(
            PropertyMetaData::fromReflectionData(...),
            $reflectionData->getProperties(),
        );

        $methods = array_map(
            FunctionMetaData::fromReflectionData(...),
            $reflectionData->getMethods(),
        );

        $constants = array_map(
            ConstantMetaData::fromReflectionData(...),
            $reflectionData->getConstants(),
        );

        $implements = array_map(
            fn($interface) => $interface->getName(),
            $reflectionData->getInterfaces(),
        );

        $attributes = array_map(
            AttributeMetaData::fromReflectionData(...),
            $reflectionData->getAttributes(),
        );

        $parentClass = $reflectionData->getParentClass();

        return new self(
            $reflectionData->getName(),
            $parentClass?->getName(),
            array_values($properties),
            array_values($methods),
            array_values($constants),
            extension: $reflectionData->getExtensionName(),
            implements: array_values($implements),
            attributes: $attributes,
            isFinal: $reflectionData->isFinal(),
            isAbstract: $reflectionData->isAbstract(),
            isReadOnly: $reflectionData->isReadOnly(),
            isDeprecated: $reflectionData->isDeprecated(),
        );
    }

    /**
     * Parse a class from a DocBook <classsynopsis> element.
     */
    public static function parseFromDoc(Element $element, string $extension): self
    {
        $doc = $element->ownerDocument;
        $xpath = new XPath($doc);
        $xpath->registerNamespace('db', 'http://docbook.org/ns/docbook');

        // Parse class name
        $classNameTags = $xpath->query('.//db:ooclass/db:classname', $element);
        assert($classNameTags->length >= 1);
        $name = $classNameTags[0]->textContent;

        // Parse modifiers
        $modifierTags = $xpath->query('.//db:ooclass/db:modifier', $element);
        $isFinal = false;
        $isAbstract = false;
        $isReadOnly = false;
        foreach ($modifierTags as $modifier) {
            match ($modifier->textContent) {
                'final' => $isFinal = true,
                'abstract' => $isAbstract = true,
                'readonly' => $isReadOnly = true,
                default => null,
            };
        }

        // Parse extends
        $extends = null;
        $extendsTags = $xpath->query('.//db:ooclass/db:classname[position()>1]', $element);
        if ($extendsTags->length === 0) {
            // Try modifier-based extends pattern
            $extendsTags = $xpath->query('.//db:classsynopsisinfo//db:classname', $element);
        }
        // Look for extends in classsynopsisinfo text
        $infoTags = $xpath->query('.//db:classsynopsisinfo', $element);
        foreach ($infoTags as $info) {
            if (str_contains($info->textContent, 'extends')) {
                $extendClassNames = $info->getElementsByTagName('classname');
                if ($extendClassNames->length > 0) {
                    $extends = $extendClassNames[0]->textContent;
                }
                break;
            }
        }

        // Parse implemented interfaces
        $implements = [];
        $interfaceTags = $xpath->query('.//db:oointerface/db:interfacename', $element);
        foreach ($interfaceTags as $interfaceTag) {
            $implements[] = $interfaceTag->textContent;
        }

        // Parse constants from fieldsynopsis with role="constant" or containing <constant>
        $constants = [];
        $fieldSynopsisTags = $xpath->query('.//db:fieldsynopsis', $element);
        foreach ($fieldSynopsisTags as $fieldTag) {
            $constantTags = $fieldTag->getElementsByTagName('constant');
            if ($constantTags->length > 0) {
                $constantName = $constantTags[0]->textContent;
                // Strip class prefix if present
                if (str_contains($constantName, '::')) {
                    $constantName = explode('::', $constantName, 2)[1];
                }
                $type = null;
                $typeTags = $fieldTag->getElementsByTagName('type');
                if ($typeTags->length > 0) {
                    $type = DocumentedTypeParser::parse($typeTags[0]);
                    if ($type instanceof SingleType) {
                        // Keep as SingleType
                    } else {
                        $type = null;
                    }
                }

                $visibility = Visibility::Public;
                $modTags = $fieldTag->getElementsByTagName('modifier');
                foreach ($modTags as $mod) {
                    $visibility = match ($mod->textContent) {
                        'private' => Visibility::Private,
                        'protected' => Visibility::Protected,
                        default => $visibility,
                    };
                }

                $constants[] = new ConstantMetaData(
                    $constantName,
                    $type instanceof SingleType ? $type : null,
                    $extension,
                    null,
                    visibility: $visibility,
                );
            }
        }

        // Parse properties from fieldsynopsis without <constant>
        $properties = [];
        foreach ($fieldSynopsisTags as $fieldTag) {
            $constantTags = $fieldTag->getElementsByTagName('constant');
            if ($constantTags->length === 0) {
                $properties[] = PropertyMetaData::parseFromDoc($fieldTag);
            }
        }

        // Parse methods from methodsynopsis
        $methods = [];
        $methodSynopsisTags = $xpath->query('.//db:methodsynopsis', $element);
        foreach ($methodSynopsisTags as $methodTag) {
            $methods[] = FunctionMetaData::parseFromDoc($methodTag, $extension);
        }

        return new self(
            $name,
            $extends,
            $properties,
            $methods,
            $constants,
            extension: $extension,
            implements: $implements,
            isFinal: $isFinal,
            isAbstract: $isAbstract,
            isReadOnly: $isReadOnly,
        );
    }
}
