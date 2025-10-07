<?php

use Girgias\StubToDocbook\Differ\ConstantListDiffer;
use Girgias\StubToDocbook\Differ\ConstantStubListDiff;
use Girgias\StubToDocbook\Differ\FunctionListDiffer;
use Girgias\StubToDocbook\Differ\FunctionStubMapDiff;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;
use Girgias\StubToDocbook\MetaData\Lists\ConstantList;

require dirname(__DIR__) . '/vendor/autoload.php';
require_once 'stub-utils.php';

/**
 * @template T of object{name: string, extension: string}
 * @param array<string, T> $symbols
 * @return array<string, array<string, T>>
 */
function symbol_list_to_extensions_map(array $symbols): array
{
    $extensions = [];
    foreach ($symbols as $name => $symbol) {
        $extensions[$symbol->extension][$name] = $symbol;
    }
    // TODO: Need to sort such that 'core' (regardless of capitalization) is first entry.
    ksort($extensions, SORT_NATURAL | SORT_FLAG_CASE);
    return $extensions;
}

/** @param array<string, array<string, ConstantMetaData>|array<string, FunctionMetaData>> $symbolsMap */
function print_extensions_map_of_symbols(array $symbolsMap): void
{
    foreach ($symbolsMap as $extension => $symbols) {
        echo "\tExtension: {$extension}\n";
        foreach ($symbols as $symbol) {
            echo "\t\t{$symbol->name}\n";
        }
    }
}

function print_constant_list_diff(ConstantStubListDiff $diff): void
{
    echo 'New constants:' . count($diff->new) . "\n";
    print_extensions_map_of_symbols(symbol_list_to_extensions_map($diff->new->constants));
    echo 'Newly deprecated constants:' . count($diff->deprecated) . "\n";
    print_extensions_map_of_symbols(symbol_list_to_extensions_map($diff->deprecated->constants));
    echo 'Removed constants:' . count($diff->removed) . "\n";
    print_extensions_map_of_symbols(symbol_list_to_extensions_map($diff->removed->constants));
}

function print_function_map_diff(FunctionStubMapDiff $diff): void {
    echo 'New functions:' . count($diff->new) . "\n";
    print_extensions_map_of_symbols(symbol_list_to_extensions_map($diff->new));
    echo 'Newly deprecated functions:' . count($diff->deprecated) . "\n";
    print_extensions_map_of_symbols(symbol_list_to_extensions_map($diff->deprecated));
    echo 'Removed functions:' . count($diff->removed) . "\n";
    print_extensions_map_of_symbols(symbol_list_to_extensions_map($diff->removed));
}

// Use array_merge_recursive with functions, classes, and constants for deprecations/BC breaks pages

$prior_version_dir = dirname(__DIR__, 2) . '/PHP-8.4/';
$master_dir = dirname(__DIR__, 2) . '/PHP-8.5/';

$prior_version_reflector = get_reflector($prior_version_dir);
$prior_version_constants = ConstantList::fromReflectionDataArray($prior_version_reflector->reflectAllConstants(), IGNORED_CONSTANTS);
$prior_version_functions = from_better_reflection_list_to_metadata(FunctionMetaData::class, $prior_version_reflector->reflectAllFunctions());

$master_reflector = get_reflector($master_dir);
$master_constants = ConstantList::fromReflectionDataArray($master_reflector->reflectAllConstants(), IGNORED_CONSTANTS);
$master_functions = from_better_reflection_list_to_metadata(FunctionMetaData::class, $master_reflector->reflectAllFunctions());

$diffC = ConstantListDiffer::stubDiff($prior_version_constants->constants, $master_constants->constants);
$diffF = FunctionListDiffer::stubDiff($prior_version_functions, $master_functions);

echo "Total 8.4 constants parsed = ", count($prior_version_constants), "; functions parsed = ", count($prior_version_functions), "\n";
echo "Total 8.5 constants parsed = ", count($master_constants), "; functions parsed = ", count($master_functions), "\n";

print_constant_list_diff($diffC);
print_function_map_diff($diffF);
