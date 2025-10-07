<?php

namespace Girgias\StubToDocbook\Tests\MetaData\Classes;

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
}
