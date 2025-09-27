<?php

use Girgias\StubToDocbook\Differ\ConstantListDiffer;
use Girgias\StubToDocbook\Differ\ConstantStubListDiff;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Lists\ConstantList;

require dirname(__DIR__) . '/vendor/autoload.php';
require_once 'stub-utils.php';

/** @return array<string, list<ConstantMetaData>> */
function list_to_extensions_list(ConstantList $constantList): array
{
    $extensions = [];
    foreach ($constantList->constants as $constant) {
        $extensions[$constant->extension][] = $constant;
    }
    ksort($extensions, SORT_NATURAL | SORT_FLAG_CASE);
    return $extensions;
}

/** @param array<string, list<ConstantMetaData>> $constantList */
function print_extensions_list_of_constants(array $constantList): void
{
    foreach ($constantList as $extension => $constants) {
        echo "\tExtension: {$extension}\n";
        foreach ($constants as $constant) {
            echo "\t\t{$constant->name} with type {$constant->type->toXml()}\n";
        }
    }
}

function print_constant_list_diff(ConstantStubListDiff $diff): void
{
    echo 'New constants:' . count($diff->new) . "\n";
    print_extensions_list_of_constants(list_to_extensions_list($diff->new));
    echo 'Newly deprecated constants:' . count($diff->deprecated) . "\n";
    print_extensions_list_of_constants(list_to_extensions_list($diff->deprecated));
    echo 'Removed constants:' . count($diff->removed) . "\n";
    print_extensions_list_of_constants(list_to_extensions_list($diff->removed));
}

// Use array_merge_recursive with functions, classes, and constants for deprecations/BC breaks pages

$prior_version_dir = dirname(__DIR__, 2) . '/PHP-8.4/';
$master_dir = dirname(__DIR__, 2) . '/PHP-8.5/';

$prior_version_reflector = get_reflector($prior_version_dir);
$prior_version_constants = ConstantList::fromReflectionDataArray($prior_version_reflector->reflectAllConstants(), IGNORED_CONSTANTS);

$master_reflector = get_reflector($master_dir);
$master_constants = ConstantList::fromReflectionDataArray($master_reflector->reflectAllConstants(), IGNORED_CONSTANTS);

$diff = ConstantListDiffer::stubDiff($prior_version_constants, $master_constants);

echo "Total 8.4 constants parsed = ", count($prior_version_constants), "\n";
echo "Total 8.5 constants parsed = ", count($master_constants), "\n";

print_constant_list_diff($diff);
