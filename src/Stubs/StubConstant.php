<?php

namespace Girgias\StubToDocbook\Stubs;

use Girgias\StubToDocbook\Types\ReflectionTypeParser;
use Girgias\StubToDocbook\Types\SingleType;
use Roave\BetterReflection\Reflection\ReflectionConstant;

final readonly class StubConstant
{
    public readonly string $name;

    private function __construct(
        string $name,
        readonly SingleType $type,
        readonly string|int|float|null $value,
        readonly string $extension,
    ) {
        /* Manual refers to true, false, and null */
        $this->name = match ($name) {
            'TRUE', 'FALSE', 'NULL' => strtolower($name),
            default => $name,
        };
    }

    public static function fromReflectionData(ReflectionConstant $reflectionData): self
    {
        return new self(
            $reflectionData->getName(),
            ReflectionTypeParser::parseTypeForConstant($reflectionData),
            $reflectionData->getValue(),
            get_extension_name_from_reflection_date($reflectionData),
        );
    }
}
