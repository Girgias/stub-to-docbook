<?php

namespace Girgias\StubToDocbook\MetaData;

use Dom\XMLDocument;
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

    public function toModifierXml(XMLDocument $document)
    {
        $modifier = $document->createElement('modifier');
        $modifier->textContent = match ($this) {
            Visibility::Public => 'public',
            Visibility::Protected => 'protected',
            Visibility::Private => 'private',
        };
        return $modifier;
    }
}
