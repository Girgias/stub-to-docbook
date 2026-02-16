<?php

namespace Girgias\StubToDocbook\MetaData\Classes;

use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\Initializer;
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
}
