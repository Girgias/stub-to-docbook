<?php

use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;
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

/**
 * @template T of object{name: string}
 * @param list<T> $list
 * @return array<string, T>
 */
function named_symbol_list_to_map(array $list): array {

    $names = array_map(fn($fn) => $fn->name, $list);
    return array_combine($names, $list);
}

/**
 * @param list<ReflectionFunction> $list
 * @return array<string, FunctionMetaData>
 */
function from_better_reflection_list_to_metadata(array $list): array
{
    $fns = array_map(
        FunctionMetaData::fromReflectionData(...),
        $list,
    );
    return named_symbol_list_to_map($fns);
}
