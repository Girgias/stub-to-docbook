<?php

use Dom\XMLDocument;
use Girgias\StubToDocbook\Differ\ConstantListDiffer;
use Girgias\StubToDocbook\Documentation\DocumentedConstantList;
use Girgias\StubToDocbook\Documentation\DocumentedConstantListType;
use Girgias\StubToDocbook\Documentation\DocumentedConstantParser;
use Girgias\StubToDocbook\Reports\ConstantReport;
use Girgias\StubToDocbook\Stubs\StubConstantList;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Girgias\StubToDocbook\Stubs\ZendEngineSingleFileSourceLocator;
use Roave\BetterReflection\BetterReflection;

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
    $dom = XMLDocument::createFromString($content);
    //echo "File $path\n";
    $listOfDocumentedConstantList = DocumentedConstantParser::parse($dom);
    $list = array_reduce($listOfDocumentedConstantList, function ($carry, DocumentedConstantList $constantList) {
        return [...$carry, ...($constantList->constants)];
    }, []);
    global $totalDocConst;
    $totalDocConst += count($list);
    return $list;
}

require dirname(__DIR__) . '/vendor/autoload.php';

$php_src_repo = dirname(__DIR__, 2) . '/PHP-8.4/';
$doc_en_repo = dirname(__DIR__, 2) . '/docs-php/en/';

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

$doc_constants = [
    // Need to be *before* as E_* constants are actually defined in reference/errorfunc/constants.xml
    $doc_en_repo . 'appendices/reserved.constants.core.xml',
    // TODO Handle properly the table parsing
    $doc_en_repo . 'appendices/tokens.xml',
    ...glob($doc_en_repo . 'reference/*/constants.xml'),
    ...glob($doc_en_repo . 'reference/*/constants_*.xml'),
];

// Ignored because they are useless or Zend debug specific
const IGNORED_CONSTANTS = [
    'ZEND_VERIFY_TYPE_INFERENCE',
    // See: https://github.com/php/php-src/pull/14724
    'MYSQLI_SET_CHARSET_DIR',
    // See: https://github.com/php/php-src/pull/6850
    'MYSQLI_NO_DATA',
    'MYSQLI_DATA_TRUNCATED',
    'MYSQLI_SERVER_QUERY_NO_GOOD_INDEX_USED',
    'MYSQLI_SERVER_QUERY_NO_INDEX_USED',
    'MYSQLI_SERVER_QUERY_WAS_SLOW',
    'MYSQLI_SERVER_PS_OUT_PARAMS',
];

$doc_constants = array_diff($doc_constants, $IGNORE_DOC_CONSTANT_FILES);

$doc_constants = array_map(file_to_doc_constants(...), $doc_constants);
$doc_constants = array_merge(...$doc_constants);
$doc_constants = new DocumentedConstantList(
    DocumentedConstantListType::VarEntryList,
    $doc_constants,
    null
);

$stubs = [
    ...glob($php_src_repo . '*/*.stub.php'),
    ...glob($php_src_repo . '*/*/*.stub.php'),
];

$IGNORE_STUB_CONSTANT_FILES = [
    // Zend_test stubs
    $php_src_repo . 'ext/zend_test/fiber.stub.php',
    $php_src_repo . 'ext/zend_test/iterators.stub.php',
    $php_src_repo . 'ext/zend_test/object_handlers.stub.php',
    $php_src_repo . 'ext/zend_test/test.stub.php',
    // DL Extension test
    $php_src_repo . 'ext/dl_test/dl_test.stub.php',
];

$stubs = array_diff($stubs, $IGNORE_STUB_CONSTANT_FILES);

$astLocator = (new BetterReflection())->astLocator();
$file_locators = array_map(
    fn (string $file) => new ZendEngineSingleFileSourceLocator($file, $astLocator),
    $stubs,
);

$reflector = ZendEngineReflector::newZendEngineReflector($file_locators);
$constants = StubConstantList::fromReflectionDataArray($reflector->reflectAllConstants(), IGNORED_CONSTANTS);
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

ConstantReport::generateHtmlReport($status, __DIR__ . '/constant-report.html');

//var_dump(array_keys($status->missing->constants));
//var_dump(array_keys($status->incorrectType->constants));
