<?php

namespace MetaData\Functions;

use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;
use Girgias\StubToDocbook\MetaData\Functions\ParameterMetaData;
use Girgias\StubToDocbook\MetaData\Initializer;
use Girgias\StubToDocbook\MetaData\InitializerVariant;
use Girgias\StubToDocbook\MetaData\Visibility;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Girgias\StubToDocbook\Tests\ZendEngineStringSourceLocator;
use Girgias\StubToDocbook\Types\SingleType;
use Girgias\StubToDocbook\Types\UnionType;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;

class FunctionMetaDataTest extends TestCase
{
    public function test_basic_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php
/**
 * @compile-time-eval
 * @refcount 1
 */
function array_column(array $array, int|string|null $column_key, int|string|null $index_key = null): array {}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectFunction('array_column');
        $fn = FunctionMetaData::fromReflectionData($reflectionFunction);

        self::assertSame('array_column', $fn->name);
        self::assertTrue(new SingleType('array')->isSame($fn->returnType));
        self::assertFalse($fn->isStatic);
        self::assertFalse($fn->isDeprecated);
        self::assertFalse($fn->byRefReturn);
        self::assertSame([], $fn->attributes);
        self::assertCount(3, $fn->parameters);
    }

    public function test_no_param_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php

function my_function(): void {}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectFunction('my_function');
        $fn = FunctionMetaData::fromReflectionData($reflectionFunction);

        self::assertSame('my_function', $fn->name);
        self::assertTrue(new SingleType('void')->isSame($fn->returnType));
        self::assertFalse($fn->isStatic);
        self::assertFalse($fn->isDeprecated);
        self::assertFalse($fn->byRefReturn);
        self::assertSame([], $fn->attributes);
        self::assertSame([], $fn->parameters);
    }

    public function test_return_type_from_doc_comment_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php

/**
 * @param resource|null $context
 * @return resource|false
 * @refcount 1
 */
function fopen(string $filename, string $mode, bool $use_include_path = false, $context = null) {}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectFunction('fopen');
        $fn = FunctionMetaData::fromReflectionData($reflectionFunction);

        self::assertSame('fopen', $fn->name);

        $expectedReturnType = new UnionType([
            new SingleType('false'),
            new SingleType('resource'),
        ]);
        self::assertTrue($expectedReturnType->isSame($fn->returnType));
        self::assertFalse($fn->isStatic);
        self::assertFalse($fn->isDeprecated);
        self::assertFalse($fn->byRefReturn);
        self::assertSame([], $fn->attributes);
        self::assertCount(4, $fn->parameters);
    }

    public function test_is_deprecated_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php
#[\Deprecated(since: '8.0', message: 'as EnchantBroker objects are freed automatically')]
function enchant_broker_free(EnchantBroker $broker): bool {}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectFunction('enchant_broker_free');
        $fn = FunctionMetaData::fromReflectionData($reflectionFunction);

        self::assertSame('enchant_broker_free', $fn->name);
        self::assertTrue($fn->isDeprecated);
        self::assertTrue(new SingleType('bool')->isSame($fn->returnType));
        self::assertFalse($fn->isStatic);
        self::assertFalse($fn->byRefReturn);
        self::assertCount(1, $fn->attributes);
        self::assertCount(1, $fn->parameters);
    }

    public function test_public_method_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    public function method(): void {}
}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectClass('Foo')->getMethod('method');
        $fn = FunctionMetaData::fromReflectionData($reflectionFunction);

        $expectedFunction = new FunctionMetaData(
            'method',
            [],
            new SingleType('void'),
            'internal',
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_protected_method_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    protected function method(): void {}
}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectClass('Foo')->getMethod('method');
        $fn = FunctionMetaData::fromReflectionData($reflectionFunction);

        $expectedFunction = new FunctionMetaData(
            'method',
            [],
            new SingleType('void'),
            'internal',
            visibility: Visibility::Protected,
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_private_method_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    private function method(): void {}
}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectClass('Foo')->getMethod('method');
        $fn = FunctionMetaData::fromReflectionData($reflectionFunction);

        $expectedFunction = new FunctionMetaData(
            'method',
            [],
            new SingleType('void'),
            'internal',
            visibility: Visibility::Private,
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_final_public_method_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    final public function method(): void {}
}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectClass('Foo')->getMethod('method');
        $fn = FunctionMetaData::fromReflectionData($reflectionFunction);

        $expectedFunction = new FunctionMetaData(
            'method',
            [],
            new SingleType('void'),
            'internal',
            isFinal: true,
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_static_public_method_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    public static function method(): void {}
}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectClass('Foo')->getMethod('method');
        $fn = FunctionMetaData::fromReflectionData($reflectionFunction);

        $expectedFunction = new FunctionMetaData(
            'method',
            [],
            new SingleType('void'),
            'internal',
            isStatic: true,
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_abstract_public_method_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php
class Foo {
    abstract public function method(): void {}
}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectClass('Foo')->getMethod('method');
        $fn = FunctionMetaData::fromReflectionData($reflectionFunction);

        $expectedFunction = new FunctionMetaData(
            'method',
            [],
            new SingleType('void'),
            'internal',
            isAbstract: true,
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_no_param_function_parsing(): void
    {
        $xml = <<<'XML'
<methodsynopsis>
 <type>string</type><methodname>test_function</methodname>
 <void/>
</methodsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $fn = FunctionMetaData::parseFromDoc($document->firstElementChild, 'none');

        $expectedFunction = new FunctionMetaData(
            'test_function',
            [],
            new SingleType('string'),
            'none',
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_variadic_function_parsing(): void
    {
        $xml = <<<'XML'
<methodsynopsis>
 <type>bool</type><methodname>test_variadic</methodname>
 <methodparam><type>mixed</type><parameter>var</parameter></methodparam>
 <methodparam rep="repeat"><type>mixed</type><parameter>vars</parameter></methodparam>
</methodsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $fn = FunctionMetaData::parseFromDoc($document->firstElementChild, 'none');

        $expectedFunction = new FunctionMetaData(
            'test_variadic',
            [
                new ParameterMetaData(
                    'var',
                    1,
                    new SingleType('mixed'),
                ),
                new ParameterMetaData(
                    'vars',
                    2,
                    new SingleType('mixed'),
                    isVariadic: true,
                ),
            ],
            new SingleType('bool'),
            'none',
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_function_parsing_with_attribute(): void
    {
        $xml = <<<'XML'
<methodsynopsis>
 <modifier role="attribute">#[\Deprecated]</modifier>
 <type>bool</type><methodname>test_attribute</methodname>
 <methodparam><type>mixed</type><parameter>param1</parameter></methodparam>
</methodsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $fn = FunctionMetaData::parseFromDoc($document->firstElementChild, 'none');

        $expectedFunction = new FunctionMetaData(
            'test_attribute',
            [
                new ParameterMetaData(
                    'param1',
                    1,
                    new SingleType('mixed'),
                ),
            ],
            new SingleType('bool'),
            'none',
            attributes: [
                new AttributeMetaData('\\Deprecated'),
            ],
            isDeprecated: true,
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_method_parsing_with_public_visibility(): void
    {
        $xml = <<<'XML'
<methodsynopsis role="WeakReference">
 <modifier>public</modifier> <type class="union"><type>object</type><type>null</type></type><methodname>WeakReference::get</methodname>
 <void/>
</methodsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $fn = FunctionMetaData::parseFromDoc($document->firstElementChild, 'none');

        $expectedFunction = new FunctionMetaData(
            'get',
            [],
            new UnionType([
                new SingleType('null'),
                new SingleType('object'),
            ]),
            'none',
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_method_parsing_with_protected_visibility(): void
    {
        /** Removed parameters for test simplicity */
        $xml = <<<'XML'
<methodsynopsis role="SplHeap">
 <modifier>protected</modifier> <type>int</type><methodname>SplHeap::compare</methodname>
 <void/>
</methodsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $fn = FunctionMetaData::parseFromDoc($document->firstElementChild, 'none');

        $expectedFunction = new FunctionMetaData(
            'compare',
            [],
            new SingleType('int'),
            'none',
            visibility: Visibility::Protected,
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_method_parsing_with_private_visibility(): void
    {
        $xml = <<<'XML'
<methodsynopsis role="ReflectionFunctionAbstract">
 <modifier>private</modifier> <type>void</type><methodname>ReflectionFunctionAbstract::__clone</methodname>
 <void/>
</methodsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $fn = FunctionMetaData::parseFromDoc($document->firstElementChild, 'none');

        $expectedFunction = new FunctionMetaData(
            '__clone',
            [],
            new SingleType('void'),
            'none',
            visibility: Visibility::Private,
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_final_method_parsing_with_public_visibility(): void
    {
        $xml = <<<'XML'
<methodsynopsis role="Exception">
 <modifier>final</modifier> <modifier>public</modifier> <type>string</type><methodname>Exception::getMessage</methodname>
 <void/>
</methodsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $fn = FunctionMetaData::parseFromDoc($document->firstElementChild, 'none');

        $expectedFunction = new FunctionMetaData(
            'getMessage',
            [],
            new SingleType('string'),
            'none',
            isFinal: true,
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_static_method_parsing_with_public_visibility(): void
    {
        $xml = <<<'XML'
<methodsynopsis role="WeakReference">
 <modifier>public</modifier> <modifier>static</modifier> <type>WeakReference</type><methodname>WeakReference::create</methodname>
 <methodparam><type>object</type><parameter>object</parameter></methodparam>
</methodsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $fn = FunctionMetaData::parseFromDoc($document->firstElementChild, 'none');

        $expectedFunction = new FunctionMetaData(
            'create',
            [
                new ParameterMetaData(
                    'object',
                    1,
                    new SingleType('object'),
                ),
            ],
            new SingleType('WeakReference'),
            'none',
            isStatic: true,
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }


    public function test_abstract_method_parsing_with_public_visibility(): void
    {
        $xml = <<<'XML'
<methodsynopsis role="Foo">
 <modifier>public</modifier> <modifier>abstract</modifier> <type>void</type><methodname>Foo::bar</methodname>
 <void/>
</methodsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $fn = FunctionMetaData::parseFromDoc($document->firstElementChild, 'none');

        $expectedFunction = new FunctionMetaData(
            'bar',
            [],
            new SingleType('void'),
            'none',
            isAbstract: true,
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_complete_function_parsing(): void
    {
        $xml = <<<'XML'
<methodsynopsis>
 <modifier role="attribute">#[\Deprecated]</modifier>
 <type class="union"><type>string</type><type>false</type></type><methodname>test_complete_function</methodname>
 <methodparam><type>string</type><parameter>param_typical</parameter></methodparam>
 <methodparam><type>array</type><parameter role="reference">param_reference</parameter></methodparam>
 <methodparam><modifier role="attribute">#[\SensitiveParameter]</modifier><type>string</type><parameter>param_sensitive</parameter></methodparam>
 <methodparam choice="opt"><type class="union"><type>int</type><type>null</type></type><parameter>param_optional</parameter><initializer><constant>SOME_CONST</constant></initializer></methodparam>
</methodsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $fn = FunctionMetaData::parseFromDoc($document->firstElementChild, 'none');

        $expectedFunction = new FunctionMetaData(
            'test_complete_function',
            [
                new ParameterMetaData(
                    'param_typical',
                    1,
                    new SingleType('string'),
                ),
                new ParameterMetaData(
                    'param_reference',
                    2,
                    new SingleType('array'),
                    isByRef: true,
                ),
                new ParameterMetaData(
                    'param_sensitive',
                    3,
                    new SingleType('string'),
                    attributes: [
                        new AttributeMetaData('\\SensitiveParameter'),
                    ],
                ),
                new ParameterMetaData(
                    'param_optional',
                    4,
                    new UnionType([
                        new SingleType('int'),
                        new SingleType('null'),
                    ]),
                    isOptional: true,
                    defaultValue: new Initializer(
                        InitializerVariant::Constant,
                        'SOME_CONST',
                    ),
                ),
            ],
            new UnionType([
                new SingleType('string'),
                new SingleType('false'),
            ]),
            'none',
            attributes: [
                new AttributeMetaData('\\Deprecated'),
            ],
            isDeprecated: true,
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }
}
