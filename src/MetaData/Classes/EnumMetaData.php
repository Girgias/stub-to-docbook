<?php

namespace Girgias\StubToDocbook\MetaData\Classes;

use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;
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

        $cases = array_values(array_map(
            EnumCaseMetaData::fromReflectionData(...),
            $reflectionData->getCases(),
        ));

        $methods = array_values(array_filter(
            array_map(
                FunctionMetaData::fromReflectionData(...),
                $reflectionData->getImmediateMethods(),
            ),
            fn (FunctionMetaData $m) => !in_array($m->name, ['cases', 'from', 'tryFrom'], true),
        ));

        $implements = $reflectionData->getInterfaceClassNames();

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
}
