<?php

namespace Girgias\StubToDocbook\Tests\MetaData\Classes;

use Girgias\StubToDocbook\MetaData\Classes\ClassMetaData;
use Girgias\StubToDocbook\MetaData\Visibility;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

class ClassMetaDataTest extends TestCase
{
    public function test_minimal_class(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Foo');
        $class = ClassMetaData::fromReflectionData($rc);

        self::assertSame('Foo', $class->name);
        self::assertNull($class->extends);
        self::assertSame([], $class->properties);
        self::assertSame([], $class->methods);
        self::assertSame([], $class->constants);
        self::assertSame([], $class->implements);
        self::assertFalse($class->isFinal);
        self::assertFalse($class->isAbstract);
        self::assertFalse($class->isReadOnly);
        self::assertFalse($class->isDeprecated);
    }

    public function test_class_with_extends_and_implements(): void
    {
        $stub = <<<'STUB'
<?php
interface Countable {
    public function count(): int;
}
class Base {}
class Child extends Base implements Countable {
    public function count(): int {}
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Child');
        $class = ClassMetaData::fromReflectionData($rc);

        self::assertSame('Child', $class->name);
        self::assertSame('Base', $class->extends);
        self::assertContains('Countable', $class->implements);
        self::assertCount(1, $class->methods);
        self::assertSame('count', $class->methods[0]->name);
    }

    public function test_final_abstract_class(): void
    {
        $stub = <<<'STUB'
<?php
abstract class AbstractFoo {
    abstract public function bar(): void;
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('AbstractFoo');
        $class = ClassMetaData::fromReflectionData($rc);

        self::assertTrue($class->isAbstract);
        self::assertFalse($class->isFinal);
    }

    public function test_class_with_properties_methods_constants(): void
    {
        $stub = <<<'STUB'
<?php
class Full {
    const MY_CONST = 42;
    public int $prop = 0;
    public function doStuff(): void {}
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Full');
        $class = ClassMetaData::fromReflectionData($rc);

        self::assertSame('Full', $class->name);
        self::assertCount(1, $class->properties);
        self::assertSame('prop', $class->properties[0]->name);
        self::assertCount(1, $class->methods);
        self::assertSame('doStuff', $class->methods[0]->name);
        self::assertCount(1, $class->constants);
        self::assertSame('MY_CONST', $class->constants[0]->name);
    }

    public function test_readonly_class(): void
    {
        $stub = <<<'STUB'
<?php
readonly class Immutable {
    public function __construct(
        public string $name,
    ) {}
}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $rc = $reflector->reflectClass('Immutable');
        $class = ClassMetaData::fromReflectionData($rc);

        self::assertTrue($class->isReadOnly);
        self::assertCount(1, $class->properties);
        self::assertSame('name', $class->properties[0]->name);
    }
}
