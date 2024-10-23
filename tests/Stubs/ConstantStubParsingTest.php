<?php

namespace Stubs;

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
STUB;

    public function test_can_retrieve_constants(): void
    {
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator(self::STUB_FILE_STR, $astLocator)
        ]);
        $constants = $reflector->reflectAllConstants();
        $constants = StubConstantList::fromReflectionDataArray($constants)->constants;

        self::assertCount(3, $constants);
        self::assertSame('E_ERROR', $constants[0]->name);
        self::assertSame('E_WARNING', $constants[1]->name);
        self::assertSame('E_PARSE', $constants[2]->name);
    }
}
