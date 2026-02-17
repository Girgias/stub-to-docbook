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

        self::assertSame('prop', $prop->name);
        self::assertNull($prop->type);
        self::assertNull($prop->defaultValue);
        self::assertSame(Visibility::Public, $prop->visibility);
        self::assertSame([], $prop->attributes);
        self::assertFalse($prop->isReadOnly);
        self::assertFalse($prop->isStatic);
        self::assertFalse($prop->isFinal);
        self::assertFalse($prop->isDeprecated);
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

        self::assertSame('prop', $prop->name);
        self::assertNull($prop->type);
        self::assertNull($prop->defaultValue);
        self::assertSame(Visibility::Protected, $prop->visibility);
        self::assertSame([], $prop->attributes);
        self::assertFalse($prop->isReadOnly);
        self::assertFalse($prop->isStatic);
        self::assertFalse($prop->isFinal);
        self::assertFalse($prop->isDeprecated);
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

        self::assertSame('prop', $prop->name);
        self::assertNull($prop->type);
        self::assertNull($prop->defaultValue);
        self::assertSame(Visibility::Private, $prop->visibility);
        self::assertSame([], $prop->attributes);
        self::assertFalse($prop->isReadOnly);
        self::assertFalse($prop->isStatic);
        self::assertFalse($prop->isFinal);
        self::assertFalse($prop->isDeprecated);
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

        self::assertSame('prop', $prop->name);
        self::assertNull($prop->type);
        self::assertNull($prop->defaultValue);
        self::assertSame(Visibility::Public, $prop->visibility);
        self::assertSame([], $prop->attributes);
        self::assertFalse($prop->isReadOnly);
        self::assertTrue($prop->isStatic);
        self::assertFalse($prop->isFinal);
        self::assertFalse($prop->isDeprecated);
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

        self::assertSame('prop', $prop->name);
        self::assertNull($prop->type);
        self::assertNull($prop->defaultValue);
        self::assertSame(Visibility::Public, $prop->visibility);
        self::assertSame([], $prop->attributes);
        self::assertFalse($prop->isReadOnly);
        self::assertFalse($prop->isStatic);
        self::assertTrue($prop->isFinal);
        self::assertFalse($prop->isDeprecated);
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

        self::assertSame('prop', $prop->name);
        self::assertTrue((new SingleType('int'))->isSame($prop->type));
        self::assertNull($prop->defaultValue);
        self::assertSame(Visibility::Public, $prop->visibility);
        self::assertSame([], $prop->attributes);
        self::assertFalse($prop->isReadOnly);
        self::assertFalse($prop->isStatic);
        self::assertFalse($prop->isFinal);
        self::assertFalse($prop->isDeprecated);
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

        self::assertSame('prop', $prop->name);
        self::assertTrue((new SingleType('int'))->isSame($prop->type));
        self::assertNull($prop->defaultValue);
        self::assertSame(Visibility::Public, $prop->visibility);
        self::assertSame([], $prop->attributes);
        self::assertTrue($prop->isReadOnly);
        self::assertFalse($prop->isStatic);
        self::assertFalse($prop->isFinal);
        self::assertFalse($prop->isDeprecated);
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

        self::assertSame('prop', $prop->name);
        self::assertNull($prop->type);
        self::assertTrue((new Initializer(InitializerVariant::Literal, '42'))->isSame($prop->defaultValue));
        self::assertSame(Visibility::Public, $prop->visibility);
        self::assertSame([], $prop->attributes);
        self::assertFalse($prop->isReadOnly);
        self::assertFalse($prop->isStatic);
        self::assertFalse($prop->isFinal);
        self::assertFalse($prop->isDeprecated);
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

        self::assertSame('prop', $prop->name);
        self::assertTrue((new SingleType('int'))->isSame($prop->type));
        self::assertTrue((new Initializer(InitializerVariant::Literal, '42'))->isSame($prop->defaultValue));
        self::assertSame(Visibility::Public, $prop->visibility);
        self::assertSame([], $prop->attributes);
        self::assertFalse($prop->isReadOnly);
        self::assertFalse($prop->isStatic);
        self::assertFalse($prop->isFinal);
        self::assertFalse($prop->isDeprecated);
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

        self::assertSame('prop', $prop->name);
        self::assertNull($prop->type);
        self::assertNull($prop->defaultValue);
        self::assertSame(Visibility::Public, $prop->visibility);
        self::assertFalse($prop->isReadOnly);
        self::assertFalse($prop->isStatic);
        self::assertFalse($prop->isFinal);
        self::assertFalse($prop->isDeprecated);

        $expectedAttr = new AttributeMetaData('\MyAttr', ['name' => new Initializer(InitializerVariant::Literal, '"bar"')]);
        self::assertCount(1, $prop->attributes);
        self::assertTrue($expectedAttr->isSame($prop->attributes[0]));
    }

    public function test_parse_from_doc_basic(): void
    {
        $xml = '<fieldsynopsis><modifier>public</modifier><type>int</type><varname>y</varname></fieldsynopsis>';
        $document = XMLDocument::createFromString($xml);
        $prop = PropertyMetaData::parseFromDoc($document->firstElementChild);

        self::assertSame('y', $prop->name);
        self::assertTrue((new SingleType('int'))->isSame($prop->type));
        self::assertSame(Visibility::Public, $prop->visibility);
        self::assertNull($prop->defaultValue);
        self::assertFalse($prop->isReadOnly);
        self::assertFalse($prop->isStatic);
    }

    public function test_parse_from_doc_protected_readonly(): void
    {
        $xml = '<fieldsynopsis><modifier>protected</modifier><modifier>readonly</modifier><type>string</type><varname>name</varname></fieldsynopsis>';
        $document = XMLDocument::createFromString($xml);
        $prop = PropertyMetaData::parseFromDoc($document->firstElementChild);

        self::assertSame('name', $prop->name);
        self::assertSame(Visibility::Protected, $prop->visibility);
        self::assertTrue($prop->isReadOnly);
    }

    public function test_parse_from_doc_with_initializer(): void
    {
        $xml = '<fieldsynopsis><modifier>public</modifier><type>int</type><varname>count</varname><initializer>0</initializer></fieldsynopsis>';
        $document = XMLDocument::createFromString($xml);
        $prop = PropertyMetaData::parseFromDoc($document->firstElementChild);

        self::assertSame('count', $prop->name);
        self::assertNotNull($prop->defaultValue);
        self::assertSame(InitializerVariant::Literal, $prop->defaultValue->variant);
        self::assertSame('0', $prop->defaultValue->value);
    }

    public function test_parse_from_doc_static(): void
    {
        $xml = '<fieldsynopsis><modifier>public</modifier><modifier>static</modifier><type>int</type><varname>instances</varname></fieldsynopsis>';
        $document = XMLDocument::createFromString($xml);
        $prop = PropertyMetaData::parseFromDoc($document->firstElementChild);

        self::assertSame('instances', $prop->name);
        self::assertTrue($prop->isStatic);
    }
}
