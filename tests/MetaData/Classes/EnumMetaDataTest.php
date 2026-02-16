<?php

namespace Girgias\StubToDocbook\Tests\MetaData\Classes;

use Girgias\StubToDocbook\MetaData\Classes\EnumCaseMetaData;
use Girgias\StubToDocbook\MetaData\Classes\EnumMetaData;
use Girgias\StubToDocbook\MetaData\Initializer;
use Girgias\StubToDocbook\MetaData\InitializerVariant;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

class EnumMetaDataTest extends TestCase
{
    public function test_unit_enum(): void
    {
        $stub = <<<'STUB'
<?php
enum Suit {
    case Hearts;
    case Diamonds;
    case Clubs;
    case Spades;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Suit');
        $enum = EnumMetaData::fromReflectionData($rc);

        self::assertSame('Suit', $enum->name);
        self::assertNull($enum->backingType);
        self::assertCount(4, $enum->cases);
        self::assertSame('Hearts', $enum->cases[0]->name);
        self::assertNull($enum->cases[0]->value);
        self::assertSame('Spades', $enum->cases[3]->name);
        self::assertFalse($enum->isDeprecated);
    }

    public function test_backed_string_enum(): void
    {
        $stub = <<<'STUB'
<?php
enum Color: string {
    case Red = 'red';
    case Blue = 'blue';
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Color');
        $enum = EnumMetaData::fromReflectionData($rc);

        self::assertSame('Color', $enum->name);
        self::assertNotNull($enum->backingType);
        self::assertTrue((new SingleType('string'))->isSame($enum->backingType));
        self::assertCount(2, $enum->cases);
        self::assertSame('Red', $enum->cases[0]->name);
        self::assertNotNull($enum->cases[0]->value);
        self::assertSame("'red'", $enum->cases[0]->value->value);
    }

    public function test_backed_int_enum(): void
    {
        $stub = <<<'STUB'
<?php
enum Priority: int {
    case Low = 1;
    case High = 10;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Priority');
        $enum = EnumMetaData::fromReflectionData($rc);

        self::assertSame('Priority', $enum->name);
        self::assertNotNull($enum->backingType);
        self::assertTrue((new SingleType('int'))->isSame($enum->backingType));
        self::assertCount(2, $enum->cases);
        self::assertSame('Low', $enum->cases[0]->name);
        self::assertTrue(
            (new Initializer(InitializerVariant::Literal, '1'))->isSame($enum->cases[0]->value)
        );
    }

    public function test_enum_with_method(): void
    {
        $stub = <<<'STUB'
<?php
enum Suit {
    case Hearts;

    public function color(): string {}
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Suit');
        $enum = EnumMetaData::fromReflectionData($rc);

        self::assertCount(1, $enum->cases);
        self::assertCount(1, $enum->methods);
        self::assertSame('color', $enum->methods[0]->name);
    }

    public function test_enum_implementing_interface(): void
    {
        $stub = <<<'STUB'
<?php
interface HasLabel {
    public function label(): string;
}
enum Suit implements HasLabel {
    case Hearts;

    public function label(): string {}
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Suit');
        $enum = EnumMetaData::fromReflectionData($rc);

        self::assertContains('HasLabel', $enum->implements);
    }
}
