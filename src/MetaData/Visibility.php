<?php

namespace Girgias\StubToDocbook\MetaData;

use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty;

enum Visibility
{
    case Public;
    case Protected;
    case Private;

    public static function fromReflectionData(ReflectionProperty|ReflectionClassConstant|ReflectionMethod $reflectionData): self
    {
        return $reflectionData->isPrivate()
            ? Visibility::Private
            : ($reflectionData->isProtected() ? Visibility::Protected : Visibility::Public);
    }
}
