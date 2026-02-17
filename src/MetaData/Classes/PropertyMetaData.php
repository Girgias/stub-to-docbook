<?php

namespace Girgias\StubToDocbook\MetaData\Classes;

use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\Initializer;
use Girgias\StubToDocbook\MetaData\Visibility;
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
