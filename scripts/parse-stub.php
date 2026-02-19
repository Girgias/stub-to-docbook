<?php

use Dom\XMLDocument;
use Girgias\StubToDocbook\Differ\ConstantListDiffer;
use Girgias\StubToDocbook\Documentation\DocumentedConstantParser;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Lists\ConstantList;
use Girgias\StubToDocbook\Reports\ConstantDocumentationReport;

$totalDocConst = 0;
/**
 * @return array<string, ConstantMetaData>
 */
function file_to_doc_constants(string $path): array
{
    $content = file_get_contents($path);
    if ($content === false) {
        // This shouldn't happen unless there is a race condition
        throw new Exception("Missing content for $path");
    }
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
    $dom = XMLDocument::createFromString($content);
    //echo "File $path\n";
    // TODO: Determine extension properly from file path
    $listOfDocumentedConstantList = DocumentedConstantParser::parse($dom, 'UNKNOWN');
    $list = array_reduce($listOfDocumentedConstantList, function ($carry, ConstantList $constantList) {
        return [...$carry, ...($constantList->constants)];
    }, []);
    global $totalDocConst;
    $totalDocConst += count($list);
    return $list;
}

require dirname(__DIR__) . '/vendor/autoload.php';
require_once 'stub-utils.php';

$php_src_repo = dirname(__DIR__, 2) . '/PHP-8.4/';
$doc_en_repo = dirname(__DIR__, 2) . '/doc-php/en/';

$IGNORE_DOC_CONSTANT_FILES = [
    // Table of constants which is whatever also external extension
    $doc_en_repo . 'reference/mqseries/constants.xml',
    // Outdated extension so whatever
    $doc_en_repo . 'reference/mysql/constants.xml',
    // External extensions, use table for constants and low prio to fix
    $doc_en_repo . 'reference/cubrid/constants.xml',
    $doc_en_repo . 'reference/pdo_cubrid/constants.xml',
    $doc_en_repo . 'reference/win32service/constants.xml',
    $doc_en_repo . 'reference/ps/constants.xml',
    $doc_en_repo . 'reference/memcache/constants.xml',
];

$doc_constants_files = [
    // Need to be *before* as E_* constants are actually defined in reference/errorfunc/constants.xml
    $doc_en_repo . 'appendices/reserved.constants.core.xml',
    // TODO Handle properly the table parsing
    $doc_en_repo . 'appendices/tokens.xml',
    ...(glob($doc_en_repo . 'reference/*/constants.xml') ?: []),
    ...(glob($doc_en_repo . 'reference/*/constants_*.xml') ?: []),
];

$doc_constants = array_diff($doc_constants_files, $IGNORE_DOC_CONSTANT_FILES);

$doc_constants = array_map(file_to_doc_constants(...), $doc_constants);
$doc_constants = array_merge(...$doc_constants);
$doc_constants = new ConstantList($doc_constants);

$reflector = get_reflector($php_src_repo);
$constants = ConstantList::fromReflectionDataArray($reflector->reflectAllConstants(), IGNORED_CONSTANTS);
//$functions = $reflector->reflectAllFunctions();
//$classes = $reflector->reflectAllClasses();

$status = ConstantListDiffer::diff($constants, $doc_constants);
echo 'There are currently:', PHP_EOL,
    count($status->missing), ' missing constants', PHP_EOL,
    count($status->incorrectTypes), ' constants with incorrect types documented', PHP_EOL,
    count($status->incorrectIdForLinking), ' documented constants with incorrect IDs for linking', PHP_EOL,
    $status->valid, ' valid constants', PHP_EOL;
echo "Total doc constants parsed = $totalDocConst\n";
echo "Total stub constants parsed = ", count($constants), "\n";

ConstantDocumentationReport::generateHtmlReport($status, __DIR__ . '/constant-report.html');

//var_dump(array_keys($status->missing->constants));
//var_dump(array_keys($status->incorrectType->constants));
