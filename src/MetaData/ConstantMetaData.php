<?php

namespace Girgias\StubToDocbook\MetaData;

use Dom\Node;
use Girgias\StubToDocbook\Types\ReflectionTypeParser;
use Girgias\StubToDocbook\Types\SingleType;
use Roave\BetterReflection\Reflection\ReflectionConstant;

final class ConstantMetaData
{
    private function __construct(
        readonly string $name,
        readonly SingleType|null $type,
        readonly string $extension,
        readonly string|null $id = null,
        readonly string|int|float|null $value = null,
        readonly Node|null $description = null,
    ) {}

    public static function fromReflectionData(ReflectionConstant $reflectionData): self
    {
        $name = $reflectionData->getName();
        $name = match ($name) {
            'TRUE', 'FALSE', 'NULL' => strtolower($name),
            default => $name,
        };
        return new self(
            $name,
            ReflectionTypeParser::parseTypeForConstant($reflectionData),
            get_extension_name_from_reflection_date($reflectionData),
            'constant.' . xmlify_labels($name),
            value: $reflectionData->getValue(),
        );
    }
}
