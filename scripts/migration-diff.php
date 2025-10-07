<?php

use Girgias\StubToDocbook\Differ\SymbolListDiffer;
use Girgias\StubToDocbook\Differ\SymbolStubMapDiff;
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

/**
 * @template T of FunctionMetaData|ConstantMetaData
 * @param array<string, array<string, T>> $symbolsMap
 */
function print_extensions_map_of_symbols(array $symbolsMap): void
{
    foreach ($symbolsMap as $extension => $symbols) {
        echo "\tExtension: {$extension}\n";
        foreach ($symbols as $symbol) {
            echo "\t\t{$symbol->name}\n";
        }
    }
}

/**
 * @template T of FunctionMetaData|ConstantMetaData
 * @param SymbolStubMapDiff<T> $diff
 * @return void
 */
function print_symbol_map_diff(string $symbolName, SymbolStubMapDiff $diff): void {
    echo 'New ', $symbolName, ':' . count($diff->new) . "\n";
    print_extensions_map_of_symbols(symbol_list_to_extensions_map($diff->new));
    echo 'Newly deprecated ', $symbolName, ':' . count($diff->deprecated) . "\n";
    print_extensions_map_of_symbols(symbol_list_to_extensions_map($diff->deprecated));
    echo 'Removed ', $symbolName, ':' . count($diff->removed) . "\n";
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

$diffC = SymbolListDiffer::stubDiff($prior_version_constants->constants, $master_constants->constants);
$diffF = SymbolListDiffer::stubDiff($prior_version_functions, $master_functions);

echo "Total 8.4 constants parsed = ", count($prior_version_constants), "; functions parsed = ", count($prior_version_functions), "\n";
echo "Total 8.5 constants parsed = ", count($master_constants), "; functions parsed = ", count($master_functions), "\n";

print_symbol_map_diff('constant', $diffC);
print_symbol_map_diff('function', $diffF);
