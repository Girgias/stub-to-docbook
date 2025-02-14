<?php

namespace Stubs;

use Girgias\StubToDocbook\Stubs\StubConstant;
use Girgias\StubToDocbook\Stubs\StubConstantList;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

class ConstantStubParsingTest extends TestCase
{
    const /* string */ STUB_FILE_STR = <<<'STUB'
<?php

/** @generate-class-entries */

/**
 * @var int
 * @cvalue E_ERROR
 */
const E_ERROR = UNKNOWN;

/**
 * @var int
 * @cvalue E_WARNING
 */
const E_WARNING = UNKNOWN;

/**
 * @var int
 * @cvalue E_PARSE
 */
const E_PARSE = UNKNOWN;

/** @var int */
const CRYPT_STD_DES = 1;
STUB;

    public function test_can_retrieve_constants(): void
    {
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator(self::STUB_FILE_STR, $astLocator),
        ]);
        $constants = $reflector->reflectAllConstants();
        $constants = StubConstantList::fromReflectionDataArray($constants)->constants;

        self::assertCount(4, $constants);
        self::assertArrayHasKey('E_ERROR', $constants);
        self::assertConstantIsSame($constants['E_ERROR'], 'E_ERROR', 'int');
        self::assertArrayHasKey('E_WARNING', $constants);
        self::assertConstantIsSame($constants['E_WARNING'], 'E_WARNING', 'int');
        self::assertArrayHasKey('E_PARSE', $constants);
        self::assertConstantIsSame($constants['E_PARSE'], 'E_PARSE', 'int');
        self::assertArrayHasKey('CRYPT_STD_DES', $constants);
        self::assertConstantIsSame($constants['CRYPT_STD_DES'], 'CRYPT_STD_DES', 'int');
    }

    private static function assertConstantIsSame(StubConstant $constant, string $name, string $type): void
    {
        self::assertSame($name, $constant->name);
        self::assertSame($type, $constant->type);
    }
}
