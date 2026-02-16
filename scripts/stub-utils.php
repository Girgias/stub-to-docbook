<?php

use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Girgias\StubToDocbook\Stubs\ZendEngineSingleFileSourceLocator;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\Reflector;

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

const IGNORED_STUB_FILES = [
    // Zend_test stubs
    'ext/zend_test/fiber.stub.php',
    'ext/zend_test/iterators.stub.php',
    'ext/zend_test/object_handlers.stub.php',
    'ext/zend_test/test.stub.php',
    // DL Extension test
    'ext/dl_test/dl_test.stub.php',
];

function get_reflector(string $path): Reflector
{
    $files = [
        ...(glob($path . '*/*.stub.php') ?: []),
        ...(glob($path . '*/*/*.stub.php') ?: []),
    ];
    $ignored_files = array_map(
        fn (string $file) => $path . $file,
        IGNORED_STUB_FILES,
    );
    $stubs = array_diff($files, $ignored_files);

    $astLocator = (new BetterReflection())->astLocator();
    $file_locators = array_map(
        function (string $file) use ($astLocator) {
            assert($file !== '');
            return new ZendEngineSingleFileSourceLocator($file, $astLocator);
        },
        $stubs,
    );

    return ZendEngineReflector::newZendEngineReflector(array_values($file_locators));
}
