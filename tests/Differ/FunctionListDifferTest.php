<?php

namespace Differ;

use Girgias\StubToDocbook\Differ\FunctionListDiffer;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Girgias\StubToDocbook\Tests\ZendEngineStringSourceLocator;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;

class FunctionListDifferTest extends TestCase
{

    const string BASE_STUB_FILE_STR = <<<'STUB'
<?php

/** @generate-class-entries */

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
    const string NEW_STUB_FILE_STR = <<<'STUB'
<?php

/** @generate-class-entries */

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

    public function test_diff_between_two_stub_files(): void
    {
        $baseSymbols = self::reflectionDataFromStubString(self::BASE_STUB_FILE_STR);
        $newSymbols = self::reflectionDataFromStubString(self::NEW_STUB_FILE_STR);

        $diff = FunctionListDiffer::stubDiff($baseSymbols, $newSymbols);
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

    /**
     * @param string $stub
     * @return array<string, FunctionMetaData>
     */
    private static function reflectionDataFromStubString(string $stub): array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        return from_better_reflection_list_to_metadata(FunctionMetaData::class, $reflector->reflectAllFunctions());
    }
}
