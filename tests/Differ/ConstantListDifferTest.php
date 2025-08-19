<?php

namespace Differ;

use Dom\XMLDocument;
use Girgias\StubToDocbook\Differ\ConstantListDiffer;
use Girgias\StubToDocbook\Documentation\DocumentedConstantList;
use Girgias\StubToDocbook\Documentation\DocumentedConstantListType;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\Stubs\StubConstantList;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Girgias\StubToDocbook\Types\SingleType;
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
const INCORRECT_LINKING_ID = UNKNOWN;

/**
 * @var int
 */
const NOT_DOCUMENTED = UNKNOWN;
STUB;
    public function testConstantListDiffer(): void
    {
        $document = XMLDocument::createEmpty();
        $docConstants = [
            'WRONG_TYPE' => new ConstantMetaData(
                "WRONG_TYPE",
                new SingleType('string'),
                'UNKNOWN',
                'constant.wrong-type',
                description: $document->createTextNode('description')
            ),
            'SOME_CONSTANT' => new ConstantMetaData(
                "SOME_CONSTANT",
                new SingleType('int'),
                'UNKNOWN',
                'constant.some-constant',
                description: $document->createTextNode('description')
            ),
            'INCORRECT_LINKING_ID' => new ConstantMetaData(
                "INCORRECT_LINKING_ID",
                new SingleType('int'),
                'UNKNOWN',
                'constant.incorrect_linking_id',
                description: $document->createTextNode('description')
            ),
        ];
        $docList = new DocumentedConstantList($docConstants, DocumentedConstantListType::VarEntryList);

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator(self::STUB_FILE_STR, $astLocator),
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
        self::assertCount(1, $stubDiff->incorrectIdForLinking);
        self::assertSame('INCORRECT_LINKING_ID', $stubDiff->incorrectIdForLinking->constants['INCORRECT_LINKING_ID']->name);
    }
}
