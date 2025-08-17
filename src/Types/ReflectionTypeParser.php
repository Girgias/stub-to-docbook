<?php

namespace Girgias\StubToDocbook\Types;

use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionUnionType;

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

    public static function convertFromReflectionType(
        ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType $reflectionType
    ): Type
    {
        if ($reflectionType instanceof ReflectionNamedType) {
            $type = $reflectionType->getName();
            if (
                $reflectionType->allowsNull()
                && $type[0] === '?'
            ) {
                $type = ltrim($type, '?');
                return new UnionType([
                    new SingleType($type),
                    new SingleType('null'),
                ]);
            }
            return new SingleType($type);
        }
        return self::fromReflectionTypeList($reflectionType);
    }

    private static function fromReflectionTypeList(
        ReflectionUnionType|ReflectionIntersectionType $type
    ): UnionType|IntersectionType {
        /** @var non-empty-list<IntersectionType|SingleType> $types */
        $types = array_map(self::convertFromReflectionType(...), $type->getTypes());
        if ($type instanceof ReflectionUnionType) {
            return new UnionType($types);
        } else {
            /** @var non-empty-list<SingleType> $types */
            return new IntersectionType($types);
        }
    }
}