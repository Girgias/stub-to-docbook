<?php

namespace Girgias\StubToDocbook\Stubs;

use Roave\BetterReflection\Reflection\ReflectionConstant;

final readonly class StubConstant
{
    private function __construct(
        readonly string $name,
        readonly string $type,
        readonly string|int|float|null $value,
        readonly string $extension
    ) {}

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
        return trim(substr($docComment, $startTypeAnnotation, $endTypeAnnotation - $startTypeAnnotation));
    }

    private static function getExtensionReflectionData(ReflectionConstant $reflectionData): string
    {
        return $reflectionData->getExtensionName() ?? 'Core';
    }
}