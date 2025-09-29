<?php

use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionClass;

function xmlify_labels(string $label): string
{
    return strtolower(str_replace('_', '-', $label));
}

function get_extension_name_from_reflection_date(
    ReflectionFunction|ReflectionConstant|ReflectionClass $reflectionData
): string
{
    return $reflectionData->getExtensionName() ?? 'Core';
}

/**
 * @param object{isDeprecated: bool} $symbol
 */
function symbol_is_deprecated(object $symbol): bool
{
    return $symbol->isDeprecated;
}
