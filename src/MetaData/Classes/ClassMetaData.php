<?php

namespace Girgias\StubToDocbook\MetaData\Classes;

use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;
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
            $reflectionData->getImmediateProperties(),
        );

        $methods = array_map(
            FunctionMetaData::fromReflectionData(...),
            $reflectionData->getImmediateMethods(),
        );

        $constants = array_map(
            ConstantMetaData::fromReflectionData(...),
            $reflectionData->getImmediateConstants(),
        );

        $implements = array_map(
            fn ($interface) => $interface->getName(),
            $reflectionData->getInterfaces(),
        );

        $attributes = array_map(
            AttributeMetaData::fromReflectionData(...),
            $reflectionData->getAttributes(),
        );

        $parentClass = $reflectionData->getParentClassName();

        return new self(
            $reflectionData->getName(),
            $parentClass,
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
}
