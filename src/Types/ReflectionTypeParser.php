<?php

namespace Girgias\StubToDocbook\Types;

use Roave\BetterReflection\Reflection\ReflectionConstant;

final class ReflectionTypeParser
{
    private static function parseTypeForConstantAsString(ReflectionConstant $reflectionData): string
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
    public static function parseTypeForConstant(ReflectionConstant $reflectionData): SingleType
    {
        return new SingleType(self::parseTypeForConstantAsString($reflectionData));
    }
}