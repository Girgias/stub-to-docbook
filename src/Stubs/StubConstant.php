<?php

namespace Girgias\StubToDocbook\Stubs;

use Roave\BetterReflection\Reflection\ReflectionConstant;

final readonly class StubConstant
{
    public readonly string $name;

    private function __construct(
        string $name,
        readonly string $type,
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
            self::getTypeFromReflectionData($reflectionData),
            $reflectionData->getValue(),
            self::getExtensionReflectionData($reflectionData),
        );
    }

    private static function getTypeFromReflectionData(ReflectionConstant $reflectionData): string
    {
        $docComment = $reflectionData->getDocComment();
        if ($docComment === null) {
            return get_debug_type($reflectionData->getValue());
        }
        $startTypeAnnotation = strpos($docComment, '@var ');
        if ($startTypeAnnotation === false) {
            return get_debug_type($reflectionData->getValue());
        }
        $startTypeAnnotation += + strlen('@var ');
        $endTypeAnnotation = strpos($docComment, "\n", $startTypeAnnotation);
        /* Single line doc comment */
        if ($endTypeAnnotation === false) {
            $endTypeAnnotation = strpos($docComment, " ", $startTypeAnnotation);
        }
        return trim(substr($docComment, $startTypeAnnotation, $endTypeAnnotation - $startTypeAnnotation));
    }

    private static function getExtensionReflectionData(ReflectionConstant $reflectionData): string
    {
        return $reflectionData->getExtensionName() ?? 'Core';
    }
}
