<?php

namespace Differ;

use Girgias\StubToDocbook\Differ\SymbolListDiffer;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;
use Girgias\StubToDocbook\MetaData\Lists\ConstantList;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Girgias\StubToDocbook\Tests\ZendEngineStringSourceLocator;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

class SymbolListDifferTest extends TestCase
{
    public const string BASE_STUB_FILE_STR = <<<'STUB'
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

function no_change(int $a, int $b): bool {}

function signature_change(int $aa): bool {}

function will_be_removed(): void {}

/**
 * @return array<string, int|string>|false
 * @refcount 1
 */
#[\Deprecated(since: '8.2', message: 'existing dep message')]
function strptime(string $timestamp, string $format): array|false {}

/**
 * @param resource $stream
 */
function will_be_deprecated($stream, int $seconds, int $microseconds = 0): bool {}
STUB;
    public const string NEW_STUB_FILE_STR = <<<'STUB'
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
 */
#[\Deprecated(since: '8.4')]
const DEPRECATED_PHP_DOC = UNKNOWN;

/**
 * @var int
 */
#[\Deprecated(since: '8.1')]
const DEPRECATED_PHP_ATTRIBUTE = UNKNOWN;

function no_change(int $a, int $b): bool {}

function signature_change(int $aa, ?string $new_param = null): bool {}

/**
 * @return array<string, int|string>|false
 * @refcount 1
 */
#[\Deprecated(since: '8.2', message: 'existing dep message')]
function strptime(string $timestamp, string $format): array|false {}

/**
 * @param resource $stream
 */
#[\Deprecated(since: '8.5', message: "new deprecation message")]
function will_be_deprecated($stream, int $seconds, int $microseconds = 0): bool {}

function new_function(int $a, int $b): bool {}
STUB;

    public function test_function_diff_between_two_stub_files(): void
    {
        $baseSymbols = self::functionMetaDataFromStubString(self::BASE_STUB_FILE_STR);
        $newSymbols = self::functionMetaDataFromStubString(self::NEW_STUB_FILE_STR);

        $diff = SymbolListDiffer::stubDiff($baseSymbols, $newSymbols);
        self::assertCount(1, $diff->new);
        self::assertArrayHasKey('new_function', $diff->new);
        self::assertSame('new_function', $diff->new['new_function']->name);

        self::assertCount(1, $diff->removed);
        self::assertArrayHasKey('will_be_removed', $diff->removed);
        self::assertSame('will_be_removed', $diff->removed['will_be_removed']->name);

        self::assertCount(1, $diff->deprecated);
        self::assertArrayHasKey('will_be_deprecated', $diff->deprecated);
        self::assertSame('will_be_deprecated', $diff->deprecated['will_be_deprecated']->name);
    }

    public function test_constant_diff_between_two_stub_files(): void
    {
        $baseConstantList = self::constantMetaDataFromStubString(self::BASE_STUB_FILE_STR);
        $newConstantList = self::constantMetaDataFromStubString(self::NEW_STUB_FILE_STR);

        $diff = SymbolListDiffer::stubDiff($baseConstantList, $newConstantList);
        self::assertCount(1, $diff->new);
        self::assertArrayHasKey('NEW_CONSTANT', $diff->new);
        self::assertSame('NEW_CONSTANT', $diff->new['NEW_CONSTANT']->name);

        self::assertCount(1, $diff->removed);
        self::assertArrayHasKey('WILL_BE_REMOVED', $diff->removed);
        self::assertSame('WILL_BE_REMOVED', $diff->removed['WILL_BE_REMOVED']->name);

        self::assertCount(1, $diff->deprecated);
        self::assertArrayHasKey('WILL_BE_DEPRECATED', $diff->deprecated);
        self::assertSame('WILL_BE_DEPRECATED', $diff->deprecated['WILL_BE_DEPRECATED']->name);
    }

    /**
     * @param non-empty-string $stub
     * @return array<string, FunctionMetaData>
     */
    private static function functionMetaDataFromStubString(string $stub): array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        return from_better_reflection_list_to_metadata(FunctionMetaData::class, $reflector->reflectAllFunctions());
    }

    /**
     * @param non-empty-string $stub
     * @return array<string, ConstantMetaData>
     */
    private static function constantMetaDataFromStubString(string $stub): array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        return ConstantList::fromReflectionDataArray($reflector->reflectAllConstants())->constants;
    }
}
