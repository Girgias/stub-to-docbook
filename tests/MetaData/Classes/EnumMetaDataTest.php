<?php

namespace Girgias\StubToDocbook\Tests\MetaData\Classes;

use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\Classes\EnumCaseMetaData;
use Girgias\StubToDocbook\MetaData\Classes\EnumMetaData;
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
        $newXml = $document->saveXml($enum->toEnumSynopsisXml($document));

        self::assertIsString($newXml);
        self::assertXmlStringEqualsXmlString($xml, $newXml);
    }

    /* TODO: add parsing tests for attributes, backing type, and deprecated the moment the XML is figured out */

    public function test_global_unit_enum_to_enum_synopsis_from_stub(): void
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

        $xml = <<<'XML'
<enumsynopsis>
 <enumname>Suit</enumname>


 <enumitem>
  <enumidentifier>Hearts</enumidentifier>
  <enumitemdescription/>
 </enumitem>

 <enumitem>
  <enumidentifier>Diamonds</enumidentifier>
  <enumitemdescription/>
 </enumitem>

 <enumitem>
  <enumidentifier>Clubs</enumidentifier>
  <enumitemdescription/>
 </enumitem>

 <enumitem>
  <enumidentifier>Spades</enumidentifier>
  <enumitemdescription/>
 </enumitem>

</enumsynopsis>
XML;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Suit');
        self::assertInstanceOf(ReflectionEnum::class, $rc);
        $enum = EnumMetaData::fromReflectionData($rc);

        $document = XMLDocument::createEmpty();
        $element = $enum->toSynopsisXml($document);
        $document->append($element);

        $newXml = $document->saveXml($element);
        self::assertIsString($newXml);
        self::assertXmlStringEqualsXmlString($xml, $newXml);
    }

    public function test_namespaced_unit_enum_to_enum_synopsis_from_stub(): void
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

        $xml = <<<'XML'
<packagesynopsis>
 <package>Foo\Bar</package>

 <enumsynopsis>
  <enumname>Suit</enumname>
 
 
  <enumitem>
   <enumidentifier>Hearts</enumidentifier>
   <enumitemdescription/>
  </enumitem>
 
  <enumitem>
   <enumidentifier>Diamonds</enumidentifier>
   <enumitemdescription/>
  </enumitem>
 
  <enumitem>
   <enumidentifier>Clubs</enumidentifier>
   <enumitemdescription/>
  </enumitem>
 
  <enumitem>
   <enumidentifier>Spades</enumidentifier>
   <enumitemdescription/>
  </enumitem>
 
 </enumsynopsis>
</packagesynopsis>
XML;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Foo\\Bar\\Suit');
        self::assertInstanceOf(ReflectionEnum::class, $rc);
        $enum = EnumMetaData::fromReflectionData($rc);

        $document = XMLDocument::createEmpty();
        $element = $enum->toSynopsisXml($document);
        $document->append($element);

        $newXml = $document->saveXml($element);
        self::assertIsString($newXml);
        self::assertXmlStringEqualsXmlString($xml, $newXml);
    }
}
