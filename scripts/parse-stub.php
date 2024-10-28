<?php

use Girgias\StubToDocbook\Differ\ConstantListDiffer;
use Girgias\StubToDocbook\Documentation\DocumentedConstantList;
use Girgias\StubToDocbook\Documentation\DocumentedConstantListType;
use Girgias\StubToDocbook\Documentation\DocumentedConstantParser;
use Girgias\StubToDocbook\Stubs\StubConstantList;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

$totalDocConst = 0;
function file_to_doc_constants(string $path) {
    $content = file_get_contents($path);
    $content = str_replace(
        [
            '&true;',
            '&false;',
            '&null;',
        ],
        [
            '<constant xmlns="http://docbook.org/ns/docbook">true</constant>',
            '<constant xmlns="http://docbook.org/ns/docbook">false</constant>',
            '<constant xmlns="http://docbook.org/ns/docbook">null</constant>',
        ],
        $content,
    );
    $content = str_replace('&', '&amp;', $content);
    $dom = new DOMDocument();
    $dom->loadXML($content);
    //echo "File $path\n";
    $list = DocumentedConstantParser::parse($dom);
    global $totalDocConst;
    $totalDocConst += count($list);
    return $list->constants;
}

require dirname(__DIR__) . '/vendor/autoload.php';

$php_src_repo = dirname(__DIR__, 2) . '/php-src/';
$doc_en_repo = dirname(__DIR__, 2) . '/docs-php/en/';


$doc_constants = [
    ...glob($doc_en_repo . 'reference/*/constants.xml'),
    $doc_en_repo . 'appendices/reserved.constants.core.xml',
];
$doc_constants = array_map(file_to_doc_constants(...), $doc_constants);
$doc_constants = array_merge(...$doc_constants);
$doc_constants = new DocumentedConstantList(
    DocumentedConstantListType::VarEntryList,
    $doc_constants,
    null
);

$stubs = [
    ...glob($php_src_repo . '*/*.stub.php'),
    //...glob($php_src_repo . '*/*/*.stub.php'),
];
$astLocator = (new BetterReflection())->astLocator();
$file_locators = array_map(
    fn (string $file) => new SingleFileSourceLocator($file, $astLocator),
    $stubs,
);

$reflector = ZendEngineReflector::newZendEngineReflector($file_locators);
$constants = StubConstantList::fromReflectionDataArray($reflector->reflectAllConstants());
//$functions = $reflector->reflectAllFunctions();
//$classes = $reflector->reflectAllClasses();

$status = ConstantListDiffer::diff($constants, $doc_constants);
echo 'There are currently:', PHP_EOL,
    count($status->missing), ' missing constants', PHP_EOL,
    count($status->incorrectType), ' constants with incorrect types documented', PHP_EOL,
    count($status->valid), ' valid constants', PHP_EOL;
echo "Total doc constants parsed = $totalDocConst\n";
echo "Total stub constants parsed = ", count($constants), "\n";
