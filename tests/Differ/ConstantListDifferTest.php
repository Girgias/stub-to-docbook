<?php

namespace Differ;

use DOMDocument;
use Girgias\StubToDocbook\Differ\ConstantListDiffer;
use Girgias\StubToDocbook\Documentation\DocumentedConstant;
use Girgias\StubToDocbook\Documentation\DocumentedConstantList;
use Girgias\StubToDocbook\Documentation\DocumentedConstantListType;
use Girgias\StubToDocbook\Stubs\StubConstant;
use Girgias\StubToDocbook\Stubs\StubConstantList;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

class ConstantListDifferTest extends TestCase
{
    const /* string */ STUB_FILE_STR = <<<'STUB'
<?php

/** @generate-class-entries */

/**
 * @var int
 */
const SOME_CONSTANT = UNKNOWN;

/**
 * @var int
 */
const WRONG_TYPE = UNKNOWN;

/**
 * @var int
 */
const NOT_DOCUMENTED = UNKNOWN;
STUB;
    public function testConstantListDiffer(): void
    {

        $document = new DOMDocument();
        $docConstants = [
            'WRONG_TYPE' => new DocumentedConstant("WRONG_TYPE", 'string', $document->createTextNode('description')),
            'SOME_CONSTANT' => new DocumentedConstant("SOME_CONSTANT", 'int', $document->createTextNode('description'))
        ];
        $docList = new DocumentedConstantList(DocumentedConstantListType::VarEntryList, $docConstants);

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator(self::STUB_FILE_STR, $astLocator)
        ]);
        $constants = $reflector->reflectAllConstants();
        $stubList = StubConstantList::fromReflectionDataArray($constants);

        $stubDiff = ConstantListDiffer::diff($stubList, $docList);
        self::assertSame(1, $stubDiff->valid);
        self::assertCount(1, $stubDiff->incorrectTypes);
        self::assertSame('WRONG_TYPE', $stubDiff->incorrectTypes['WRONG_TYPE'][0]->name);
        self::assertSame('string', $stubDiff->incorrectTypes['WRONG_TYPE'][1]);
        self::assertCount(1, $stubDiff->missing);
        self::assertSame('NOT_DOCUMENTED', $stubDiff->missing->constants['NOT_DOCUMENTED']->name);
    }
}
