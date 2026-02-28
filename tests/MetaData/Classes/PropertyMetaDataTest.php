<?php

namespace Girgias\StubToDocbook\Tests\MetaData\Classes;

use Dom\XMLDocument;
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
}
