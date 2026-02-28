<?php

namespace Girgias\StubToDocbook\Tests\MetaData\Classes;

use Dom\XMLDocument;
use Girgias\StubToDocbook\FP\Utils;
use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\Classes\PropertyMetaData;
use Girgias\StubToDocbook\MetaData\Initializer;
use Girgias\StubToDocbook\MetaData\InitializerVariant;
use Girgias\StubToDocbook\MetaData\Visibility;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

class PropertyMetaDataTest extends TestCase
{
    public function test_minimal_property(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    public $prop;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Foo');
        $prop = PropertyMetaData::fromReflectionData($rc->getProperty('prop'));

        $expectedProperty = new PropertyMetaData(
            'prop',
            null,
        );
        self::assertTrue($expectedProperty->isSame($prop));
    }

    public function test_protected_property(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    protected $prop;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Foo');
        $prop = PropertyMetaData::fromReflectionData($rc->getProperty('prop'));

        $expectedProperty = new PropertyMetaData(
            'prop',
            null,
            visibility: Visibility::Protected,
        );
        self::assertTrue($expectedProperty->isSame($prop));
    }

    public function test_private_property(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    private $prop;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Foo');
        $prop = PropertyMetaData::fromReflectionData($rc->getProperty('prop'));

        $expectedProperty = new PropertyMetaData(
            'prop',
            null,
            visibility: Visibility::Private,
        );
        self::assertTrue($expectedProperty->isSame($prop));
    }

    public function test_static_property(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    public static $prop;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Foo');
        $prop = PropertyMetaData::fromReflectionData($rc->getProperty('prop'));

        $expectedProperty = new PropertyMetaData(
            'prop',
            null,
            isStatic: true,
        );
        self::assertTrue($expectedProperty->isSame($prop));
    }

    public function test_final_property(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    final public $prop;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Foo');
        $prop = PropertyMetaData::fromReflectionData($rc->getProperty('prop'));

        $expectedProperty = new PropertyMetaData(
            'prop',
            null,
            isFinal: true,
        );
        self::assertTrue($expectedProperty->isSame($prop));
    }

    public function test_typed_property(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    public int $prop;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Foo');
        $prop = PropertyMetaData::fromReflectionData($rc->getProperty('prop'));

        $expectedProperty = new PropertyMetaData(
            'prop',
            new SingleType('int'),
        );
        self::assertTrue($expectedProperty->isSame($prop));
    }

    public function test_typed_readonly_property(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    public readonly int $prop;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Foo');
        $prop = PropertyMetaData::fromReflectionData($rc->getProperty('prop'));

        $expectedProperty = new PropertyMetaData(
            'prop',
            new SingleType('int'),
            isReadOnly: true,
        );
        self::assertTrue($expectedProperty->isSame($prop));
    }

    public function test_property_with_default_value(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    public $prop = 42;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Foo');
        $prop = PropertyMetaData::fromReflectionData($rc->getProperty('prop'));

        $expectedProperty = new PropertyMetaData(
            'prop',
            null,
            defaultValue: new Initializer(InitializerVariant::Literal, '42'),
        );
        self::assertTrue($expectedProperty->isSame($prop));
    }

    public function test_typed_property_with_default_value(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    public int $prop = 42;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Foo');
        $prop = PropertyMetaData::fromReflectionData($rc->getProperty('prop'));

        $expectedProperty = new PropertyMetaData(
            'prop',
            new SingleType('int'),
            defaultValue: new Initializer(InitializerVariant::Literal, '42'),
        );
        self::assertTrue($expectedProperty->isSame($prop));
    }

    public function test_property_with_attribute(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    #[\MyAttr(name: "bar")]
    public $prop;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Foo');
        $prop = PropertyMetaData::fromReflectionData($rc->getProperty('prop'));

        $expectedProperty = new PropertyMetaData(
            'prop',
            null,
            attributes: [
                new AttributeMetaData(
                    '\MyAttr',
                    ['name' => new Initializer(InitializerVariant::Literal, '"bar"')],
                ),
            ],
        );
        self::assertTrue($expectedProperty->isSame($prop));
    }

    public function test_property_with_deprecated_attribute(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    #[\Deprecated]
    public $prop;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Foo');
        $prop = PropertyMetaData::fromReflectionData($rc->getProperty('prop'));

        $expectedProperty = new PropertyMetaData(
            'prop',
            null,
            attributes: [
                new AttributeMetaData('\Deprecated'),
            ],
            isDeprecated: true,
        );
        self::assertTrue($expectedProperty->isSame($prop));
    }

    public function test_parse_from_doc_basic(): void
    {
        $xml = <<<'XML'
<fieldsynopsis>
 <modifier>public</modifier>
 <type>int</type>
 <varname>y</varname>
</fieldsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $prop = PropertyMetaData::parseFromDoc($document->firstElementChild);

        $expectedProperty = new PropertyMetaData(
            'y',
            new SingleType('int'),
        );
        self::assertTrue($expectedProperty->isSame($prop));
    }

    public function test_parse_from_doc_protected_readonly(): void
    {
        $xml = <<<'XML'
<fieldsynopsis>
 <modifier>protected</modifier>
 <modifier>readonly</modifier>
 <type>string</type>
 <varname>name</varname>
</fieldsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $prop = PropertyMetaData::parseFromDoc($document->firstElementChild);

        $expectedProperty = new PropertyMetaData(
            'name',
            new SingleType('string'),
            visibility: Visibility::Protected,
            isReadOnly: true,
        );
        self::assertTrue($expectedProperty->isSame($prop));
    }

    public function test_parse_from_doc_with_initializer(): void
    {
        $xml = <<<'XML'
<fieldsynopsis>
 <modifier>public</modifier>
 <type>int</type>
 <varname>count</varname>
 <initializer>0</initializer>
</fieldsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $prop = PropertyMetaData::parseFromDoc($document->firstElementChild);

        $expectedProperty = new PropertyMetaData(
            'count',
            new SingleType('int'),
            defaultValue: new Initializer(InitializerVariant::Literal, '0'),
        );
        self::assertTrue($expectedProperty->isSame($prop));
    }

    public function test_parse_from_doc_final(): void
    {
        $xml = <<<'XML'
<fieldsynopsis>
 <modifier>final</modifier>
 <modifier>public</modifier>
 <type>int</type>
 <varname>propName</varname>
</fieldsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $prop = PropertyMetaData::parseFromDoc($document->firstElementChild);

        $expectedProperty = new PropertyMetaData(
            'propName',
            new SingleType('int'),
            isFinal: true,
        );
        self::assertTrue($expectedProperty->isSame($prop));
    }

    public function test_parse_from_doc_static(): void
    {
        $xml = <<<'XML'
<fieldsynopsis>
 <modifier>public</modifier>
 <modifier>static</modifier>
 <type>int</type>
 <varname>instances</varname>
</fieldsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $prop = PropertyMetaData::parseFromDoc($document->firstElementChild);

        $expectedProperty = new PropertyMetaData(
            'instances',
            new SingleType('int'),
            isStatic: true,
        );
        self::assertTrue($expectedProperty->isSame($prop));
    }

    public function test_to_field_synopsis_xml_basic(): void
    {
        $xml = <<<'XML'
<fieldsynopsis>
 <modifier>public</modifier>
 <type>int</type>
 <varname>propName</varname>
</fieldsynopsis>
XML;

        $prop = new PropertyMetaData(
            'propName',
            new SingleType('int'),
        );

        $document = XMLDocument::createEmpty();
        $newXml = $document->saveXml($prop->toFieldSynopsisXml($document));

        self::assertIsString($newXml);
        self::assertXmlStringEqualsXmlString($xml, $newXml);
    }

    public function test_to_field_synopsis_xml_readonly(): void
    {
        $xml = <<<'XML'
<fieldsynopsis>
 <modifier>public</modifier>
 <modifier>readonly</modifier>
 <type>int</type>
 <varname>propName</varname>
</fieldsynopsis>
XML;

        $prop = new PropertyMetaData(
            'propName',
            new SingleType('int'),
            isReadOnly: true,
        );

        $document = XMLDocument::createEmpty();
        $newXml = $document->saveXml($prop->toFieldSynopsisXml($document));

        self::assertIsString($newXml);
        self::assertXmlStringEqualsXmlString($xml, $newXml);
    }

    public function test_to_field_synopsis_xml_static(): void
    {
        $xml = <<<'XML'
<fieldsynopsis>
 <modifier>public</modifier>
 <modifier>static</modifier>
 <type>int</type>
 <varname>propName</varname>
</fieldsynopsis>
XML;

        $prop = new PropertyMetaData(
            'propName',
            new SingleType('int'),
            isStatic: true,
        );

        $document = XMLDocument::createEmpty();
        $newXml = $document->saveXml($prop->toFieldSynopsisXml($document));

        self::assertIsString($newXml);
        self::assertXmlStringEqualsXmlString($xml, $newXml);
    }

    public function test_to_field_synopsis_xml_initializer(): void
    {
        $xml = <<<'XML'
<fieldsynopsis>
 <modifier>public</modifier>
 <type>int</type>
 <varname>propName</varname>
 <initializer><literal>42</literal></initializer>
</fieldsynopsis>
XML;

        $prop = new PropertyMetaData(
            'propName',
            new SingleType('int'),
            defaultValue: new Initializer(InitializerVariant::Literal, '42'),
        );

        $document = XMLDocument::createEmpty();
        $newXml = $document->saveXml($prop->toFieldSynopsisXml($document));

        self::assertIsString($newXml);
        self::assertXmlStringEqualsXmlString($xml, $newXml);
    }

    public function test_to_field_synopsis_xml_final(): void
    {
        $xml = <<<'XML'
<fieldsynopsis>
 <modifier>final</modifier>
 <modifier>public</modifier>
 <type>int</type>
 <varname>propName</varname>
</fieldsynopsis>
XML;

        $prop = new PropertyMetaData(
            'propName',
            new SingleType('int'),
            isFinal: true,
        );

        $document = XMLDocument::createEmpty();
        $element = $prop->toFieldSynopsisXml($document);
        $newXml = $document->saveXml($element);

        self::assertIsString($newXml);
        self::assertXmlStringEqualsXmlString($xml, $newXml);
    }

    /** TODO: Mode integration tests to another class
     * #[PHPUnit\Framework\Attributes\CoversNothing]
     * */
    public function test_stub_e2e_tests()
    {
        $stub = <<<'STUB'
<?php
class Foo {
    public $prop1;
    public int $prop2;
    public int $prop3 = 42;
    public readonly int $prop4;
    final public string $prop5;
    public static string $prop6;
    #[\MyAttr(name: "bar")]
    public $prop7;
    #[\Deprecated]
    public $prop8;
    protected $prop9;
    private $prop10;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $reflectionData = $reflector->reflectClass('Foo');

        $props = array_map(
            PropertyMetaData::fromReflectionData(...),
            $reflectionData->getProperties(),
        );

        $fn = static function (PropertyMetaData $prop): string {
            $document = XMLDocument::createEmpty();
            return $document->saveXml($prop->toFieldSynopsisXml($document));
        };
        $xmls = array_map(
            $fn,
            $props,
        );

        $fn2 = static function (string $rawXml): PropertyMetaData {
            $document = XMLDocument::createFromString($rawXml);
            return PropertyMetaData::parseFromDoc($document->firstElementChild);
        };
        $parsedProps = array_map(
            $fn2,
            $xmls,
        );

        self::assertTrue(Utils::equateList($props, $parsedProps));
    }
}
