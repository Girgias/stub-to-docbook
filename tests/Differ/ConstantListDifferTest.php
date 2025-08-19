<?php

namespace Differ;

use Dom\XMLDocument;
use Girgias\StubToDocbook\Differ\ConstantListDiffer;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Lists\ConstantList;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

class ConstantListDifferTest extends TestCase
{
    const string STUB_FILE_STR = <<<'STUB'
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

    const string BASE_STUB_FILE_STR = <<<'STUB'
<?php

/** @generate-class-entries */

/**
 * @var int
 */
const EXISTING_CONSTANT = UNKNOWN;

/**
 * @var int
 */
const CHANGED_TYPE = UNKNOWN;

/**
 * @var int
 */
const WILL_BE_REMOVED = UNKNOWN;

/**
 * @var int
 */
const WILL_BE_DEPRECATED = UNKNOWN;

/**
 * @var int
 * @deprecated
 */
const DEPRECATED_PHP_DOC = UNKNOWN;

/**
 * @var int
 */
#[\Deprecated(since: '8.1')]
const DEPRECATED_PHP_ATTRIBUTE = UNKNOWN;
STUB;
    const string NEW_STUB_FILE_STR = <<<'STUB'
<?php

/** @generate-class-entries */

/**
 * @var int
 */
const EXISTING_CONSTANT = UNKNOWN;

/**
 * @var bool
 */
const CHANGED_TYPE = UNKNOWN;

/**
 * @var int
 */
const NEW_CONSTANT = UNKNOWN;

/**
 * @var int
 */
#[\Deprecated(since: '8.5')]
const WILL_BE_DEPRECATED = UNKNOWN;

/**
 * @var int
 * @deprecated
 */
const DEPRECATED_PHP_DOC = UNKNOWN;

/**
 * @var int
 */
#[\Deprecated(since: '8.1')]
const DEPRECATED_PHP_ATTRIBUTE = UNKNOWN;
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
        $docList = new ConstantList($docConstants);

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator(self::STUB_FILE_STR, $astLocator),
        ]);
        $constants = $reflector->reflectAllConstants();
        $stubList = ConstantList::fromReflectionDataArray($constants);

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

    public function test_diff_between_two_stub_files(): void
    {
        $astLocator = (new BetterReflection())->astLocator();

        $baseReflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator(self::BASE_STUB_FILE_STR, $astLocator),
        ]);
        $baseConstants = $baseReflector->reflectAllConstants();
        $baseConstantList = ConstantList::fromReflectionDataArray($baseConstants);

        $newReflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator(self::NEW_STUB_FILE_STR, $astLocator),
        ]);
        $newConstants = $newReflector->reflectAllConstants();
        $newConstantList = ConstantList::fromReflectionDataArray($newConstants);

        $diff = ConstantListDiffer::stubDiff($baseConstantList, $newConstantList);
        self::assertCount(1, $diff->new);
        self::assertArrayHasKey('NEW_CONSTANT', $diff->new->constants);
        self::assertSame('NEW_CONSTANT', $diff->new->constants['NEW_CONSTANT']->name);

        self::assertCount(1, $diff->removed);
        self::assertArrayHasKey('WILL_BE_REMOVED', $diff->removed->constants);
        self::assertSame('WILL_BE_REMOVED', $diff->removed->constants['WILL_BE_REMOVED']->name);
    }
}
