<?php

namespace Girgias\StubToDocbook\Tests\MetaData\Classes;

use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\Classes\EnumCaseMetaData;
use Girgias\StubToDocbook\MetaData\Classes\EnumMetaData;
use Girgias\StubToDocbook\MetaData\DescriptionVariant;
use Girgias\StubToDocbook\MetaData\Initializer;
use Girgias\StubToDocbook\MetaData\InitializerVariant;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Girgias\StubToDocbook\Tests\ZendEngineStringSourceLocator;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionEnum;

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
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Suit');
        self::assertInstanceOf(ReflectionEnum::class, $rc);
        $enum = EnumMetaData::fromReflectionData($rc);

        self::assertSame('Suit', $enum->name);
        self::assertNull($enum->namespace);
        self::assertNull($enum->backingType);
        self::assertCount(4, $enum->cases);
        self::assertSame('Hearts', $enum->cases[0]->name);
        self::assertNull($enum->cases[0]->value);
        self::assertSame('Spades', $enum->cases[3]->name);
        self::assertSame([], $enum->implements);
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
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Color');
        self::assertInstanceOf(ReflectionEnum::class, $rc);
        $enum = EnumMetaData::fromReflectionData($rc);

        self::assertSame('Color', $enum->name);
        self::assertNull($enum->namespace);
        self::assertNotNull($enum->backingType);
        self::assertTrue((new SingleType('string'))->isSame($enum->backingType));
        self::assertCount(2, $enum->cases);
        self::assertSame('Red', $enum->cases[0]->name);
        self::assertNotNull($enum->cases[0]->value);
        self::assertSame("'red'", $enum->cases[0]->value->value);
        self::assertSame([], $enum->implements);
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
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Priority');
        self::assertInstanceOf(ReflectionEnum::class, $rc);
        $enum = EnumMetaData::fromReflectionData($rc);

        self::assertSame('Priority', $enum->name);
        self::assertNotNull($enum->backingType);
        self::assertTrue((new SingleType('int'))->isSame($enum->backingType));
        self::assertCount(2, $enum->cases);
        self::assertSame('Low', $enum->cases[0]->name);
        self::assertTrue(
            (new Initializer(InitializerVariant::Literal, '1'))->isSame($enum->cases[0]->value),
        );
        self::assertSame([], $enum->implements);
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
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Suit');
        self::assertInstanceOf(ReflectionEnum::class, $rc);
        $enum = EnumMetaData::fromReflectionData($rc);

        self::assertCount(1, $enum->cases);
        self::assertCount(1, $enum->methods);
        self::assertSame('color', $enum->methods[0]->name);
        self::assertSame([], $enum->implements);
    }

    public function test_deprecated_enum_case(): void
    {
        $stub = <<<'STUB'
<?php
enum Suit {
    case Hearts;
    #[\Deprecated]
    case Diamonds;
    case Clubs;
    case Spades;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Suit');
        self::assertInstanceOf(ReflectionEnum::class, $rc);
        $enum = EnumMetaData::fromReflectionData($rc);

        self::assertCount(4, $enum->cases);
        self::assertFalse($enum->cases[0]->isDeprecated);
        self::assertEmpty($enum->cases[0]->attributes);
        self::assertTrue($enum->cases[1]->isDeprecated);
        self::assertSame('Diamonds', $enum->cases[1]->name);
        self::assertCount(1, $enum->cases[1]->attributes);
        self::assertSame('\Deprecated', $enum->cases[1]->attributes[0]->name);
        self::assertSame([], $enum->implements);
    }

    public function test_enum_with_attribute(): void
    {
        $stub = <<<'STUB'
<?php
#[\Deprecated]
enum Suit {
    case Hearts;
    case Diamonds;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Suit');
        self::assertInstanceOf(ReflectionEnum::class, $rc);
        $enum = EnumMetaData::fromReflectionData($rc);

        self::assertTrue($enum->isDeprecated);
        self::assertCount(1, $enum->attributes);
        self::assertSame('\Deprecated', $enum->attributes[0]->name);
        self::assertCount(2, $enum->cases);
        self::assertFalse($enum->cases[0]->isDeprecated);
        self::assertFalse($enum->cases[1]->isDeprecated);
        self::assertSame([], $enum->implements);
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
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Suit');
        self::assertInstanceOf(ReflectionEnum::class, $rc);
        $enum = EnumMetaData::fromReflectionData($rc);

        self::assertCount(1, $enum->implements);
        self::assertContains('HasLabel', $enum->implements);
    }

    public function test_unit_enum_in_namespace(): void
    {
        $stub = <<<'STUB'
<?php
namespace Foo\Bar;

enum Suit {
    case Hearts;
    case Diamonds;
    case Clubs;
    case Spades;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Foo\\Bar\\Suit');
        self::assertInstanceOf(ReflectionEnum::class, $rc);
        $enum = EnumMetaData::fromReflectionData($rc);

        self::assertSame('Suit', $enum->name);
        self::assertSame('Foo\\Bar', $enum->namespace);
        self::assertNull($enum->backingType);
        self::assertCount(4, $enum->cases);
        self::assertSame('Hearts', $enum->cases[0]->name);
        self::assertNull($enum->cases[0]->value);
        self::assertSame('Spades', $enum->cases[3]->name);
        self::assertFalse($enum->isDeprecated);
    }

    public function test_unit_enum_with_description(): void
    {
        $stub = <<<'STUB'
<?php
/**
 * LogLevel represents the severity of a log message.
 * It can be used to filter log messages based on their importance.
 */
enum LogLevel {
    /**
     * Error log level represents critical issues that require immediate attention.
     */
    case Error = 1;
    /**
     * Warning log level represents non-critical issues that should be addressed but do not require immediate attention.
     */
    case Warning;
    /**
     * Info log level represents errors that are not critical 
     * and can be ignored in most cases.
     * 
     * @deprecated Info log level is deprecated and should not be used in new code.
     */
    case Info;

    case Trace;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('LogLevel');
        self::assertInstanceOf(ReflectionEnum::class, $rc);
        $enum = EnumMetaData::fromReflectionData($rc);

        self::assertSame('LogLevel', $enum->name);
        self::assertSame(DescriptionVariant::Text, $enum->description->variant);
        self::assertSame(
            'LogLevel represents the severity of a log message.' . PHP_EOL
            . 'It can be used to filter log messages based on their importance.',
            $enum->description->value,
        );

        self::assertNull($enum->backingType);
        self::assertCount(4, $enum->cases);

        self::assertSame('Error', $enum->cases[0]->name);
        self::assertEquals(
            new Initializer(InitializerVariant::Literal, '1'),
            $enum->cases[0]->value,
        );

        self::assertSame(DescriptionVariant::Enum, $enum->cases[0]->description->variant);
        self::assertSame(
            'Error log level represents critical issues that require immediate attention.',
            $enum->cases[0]->description->value,
        );

        self::assertSame('Warning', $enum->cases[1]->name);
        self::assertNull($enum->cases[1]->value);

        self::assertSame(DescriptionVariant::Enum, $enum->cases[1]->description->variant);
        self::assertSame(
            'Warning log level represents non-critical issues that should be addressed but do not require immediate attention.',
            $enum->cases[1]->description->value,
        );

        self::assertSame('Info', $enum->cases[2]->name);

        self::assertSame(DescriptionVariant::Enum, $enum->cases[2]->description->variant);
        self::assertSame(
            'Info log level represents errors that are not critical' . PHP_EOL
             . 'and can be ignored in most cases.',
            $enum->cases[2]->description->value,
        );

        self::assertSame('Trace', $enum->cases[3]->name);
        self::assertSame(null, $enum->cases[3]->value);
        self::assertSame(null, $enum->cases[3]->description);

        self::assertFalse($enum->isDeprecated);
    }

    public function test_unit_enum_parsing(): void
    {
        $xml = <<<'XML'
<enumsynopsis>
 <enumname>RoundingMode</enumname>

 <enumitem>
  <enumidentifier>HalfAwayFromZero</enumidentifier>
  <enumitemdescription>
   Round to the nearest integer.
   If the decimal part is <literal>5</literal>,
   round to the integer with the larger absolute value.
  </enumitemdescription>
 </enumitem>

 <enumitem>
  <enumidentifier>HalfTowardsZero</enumidentifier>
  <enumitemdescription>
   Round to the nearest integer.
   If the decimal part is <literal>5</literal>,
   round to the integer with the smaller absolute value.
  </enumitemdescription>
 </enumitem>

 <enumitem>
  <enumidentifier>HalfEven</enumidentifier>
  <enumitemdescription>
   Round to the nearest integer.
   If the decimal part is <literal>5</literal>,
   round to the even integer.
  </enumitemdescription>
 </enumitem>

 <enumitem>
  <enumidentifier>HalfOdd</enumidentifier>
  <enumitemdescription>
   Round to the nearest integer.
   If the decimal part is <literal>5</literal>,
   round to the odd integer.
  </enumitemdescription>
 </enumitem>

 <enumitem>
  <enumidentifier>TowardsZero</enumidentifier>
  <enumitemdescription>
   Round to the nearest integer with a smaller or equal absolute value.
  </enumitemdescription>
 </enumitem>

 <enumitem>
  <enumidentifier>AwayFromZero</enumidentifier>
  <enumitemdescription>
   Round to the nearest integer with a greater or equal absolute value.
  </enumitemdescription>
 </enumitem>

 <enumitem>
  <enumidentifier>NegativeInfinity</enumidentifier>
  <enumitemdescription>
   Round to the largest integer that is smaller or equal.
  </enumitemdescription>
 </enumitem>

 <enumitem>
  <enumidentifier>PositiveInfinity</enumidentifier>
  <enumitemdescription>
   Round to the smallest integer that is greater or equal.
  </enumitemdescription>
 </enumitem>

</enumsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $enum = EnumMetaData::parseFromDoc($document->firstElementChild, 'none', null);

        self::assertSame('RoundingMode', $enum->name);
        self::assertNull($enum->backingType);
        self::assertCount(8, $enum->cases);
        self::assertSame('HalfAwayFromZero', $enum->cases[0]->name);
        self::assertSame([], $enum->attributes);
    }

    public function test_unit_to_enum_synopsis(): void
    {
        $xml = <<<'XML'
<enumsynopsis>
 <enumname>LogLevel</enumname>
 <enumitem>
  <enumidentifier>Error</enumidentifier>
  <enumvalue>1</enumvalue>
  <enumitemdescription/>
 </enumitem>
 <enumitem>
  <enumidentifier>Warning</enumidentifier>
  <enumitemdescription/>
 </enumitem>
</enumsynopsis>
XML;

        $enum = new EnumMetaData(
            'LogLevel',
            backingType: null,
            cases: [
                new EnumCaseMetaData('Error', new Initializer(InitializerVariant::Literal, '1')),
                new EnumCaseMetaData('Warning'),
            ],
            methods: [],
            extension: 'internal',
        );

        $document = XMLDocument::createEmpty();
        $newXml = $document->saveXml($enum->toEnumSenumsynopsisXML($document));

        self::assertIsString($newXml);
        self::assertXmlStringEqualsXmlString($xml, $newXml);
    }

    /* TODO: add parsing tests for attributes, backing type, and deprecated the moment the XML is figured out */
}
