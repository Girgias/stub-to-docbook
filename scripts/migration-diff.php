<?php

use Girgias\StubToDocbook\Differ\ConstantListDiffer;
use Girgias\StubToDocbook\MetaData\Lists\ConstantList;

require dirname(__DIR__) . '/vendor/autoload.php';
require_once 'stub-utils.php';

$prior_version_dir = dirname(__DIR__, 2) . '/PHP-8.4/';
$master_dir = dirname(__DIR__, 2) . '/PHP-8.5/';

$prior_version_reflector = get_reflector($prior_version_dir);
$prior_version_constants = ConstantList::fromReflectionDataArray($prior_version_reflector->reflectAllConstants(), IGNORED_CONSTANTS);

$master_reflector = get_reflector($master_dir);
$master_constants = ConstantList::fromReflectionDataArray($master_reflector->reflectAllConstants(), IGNORED_CONSTANTS);

$diff = ConstantListDiffer::stubDiff($prior_version_constants, $master_constants);

echo 'There are currently:', PHP_EOL,
    count($diff->new), ' new constants', PHP_EOL,
    count($diff->removed), ' constants have been removed', PHP_EOL;

echo "Total 8.4 constants parsed = ", count($prior_version_constants), "\n";
echo "Total 8.5 constants parsed = ", count($master_constants), "\n";

foreach ($diff->new->constants as $constant) {
    echo 'New constant ', $constant->name, ' from extension ', $constant->extension, ' with type:', "\n",
    $constant->type->toXml(), "\n";
}
foreach ($diff->deprecated->constants as $constant) {
    echo 'Newly deprecated constant ', $constant->name, ' from extension ', $constant->extension, ' with type:', "\n",
    $constant->type->toXml(), "\n";
}
foreach ($diff->removed->constants as $constant) {
    echo 'Removed constant ', $constant->name, ' from extension ', $constant->extension, ' with type:', "\n",
    $constant->type->toXml(), "\n";
}
