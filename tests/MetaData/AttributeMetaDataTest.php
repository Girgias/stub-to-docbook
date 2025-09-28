<?php

namespace MetaData;

use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\Initializer;
use Girgias\StubToDocbook\MetaData\InitializerVariant;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

class AttributeMetaDataTest extends TestCase
{
    public function test_attribute_parsing(): void
    {
        $document = XMLDocument::createFromString('<modifier role="attribute">#[\Deprecated]</modifier>');
        $attribute = AttributeMetaData::parseFromDoc($document->firstElementChild);
        self::assertSame('\\Deprecated', $attribute->name);

        $expected = new AttributeMetaData(
            '\\Deprecated',
            [],
        );
        self::assertTrue($expected->isSame($attribute));
    }

    public function test_attribute_with_arguments_parsing(): void
    {
        $document = XMLDocument::createFromString('<modifier role="attribute">#[\Deprecated(since: "8.1", message: "as it was unused")]</modifier>');
        $attribute = AttributeMetaData::parseFromDoc($document->firstElementChild);
        self::assertSame('\\Deprecated', $attribute->name);
        self::assertCount(2, $attribute->arguments);
        self::assertArrayHasKey('since', $attribute->arguments);
        self::assertSame(InitializerVariant::Literal, $attribute->arguments['since']->variant);
        self::assertSame('"8.1"', $attribute->arguments['since']->value);
        self::assertArrayHasKey('message', $attribute->arguments);
        self::assertSame(InitializerVariant::Literal, $attribute->arguments['message']->variant);
        self::assertSame('"as it was unused"', $attribute->arguments['message']->value);
    }

    public function test_attribute_from_reflection_data_string_arg(): void
    {
        $stub = <<<'STUB'
<?php
#[\Deprecated(since: '8.1')]
const SOME_CONST = 5;
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $reflectionData = $reflector->reflectConstant('SOME_CONST')->getAttributes();
        self::assertCount(1, $reflectionData);
        $attribute = AttributeMetaData::fromReflectionData($reflectionData[0]);
        self::assertSame('\\Deprecated', $attribute->name);
        self::assertCount(1, $attribute->arguments);
        self::assertArrayHasKey('since', $attribute->arguments);
        self::assertInstanceOf(Initializer::class, $attribute->arguments['since']);
        self::assertSame(InitializerVariant::Literal, $attribute->arguments['since']->variant);
        self::assertSame('\'8.1\'', $attribute->arguments['since']->value);

        $expected = new AttributeMetaData(
            '\\Deprecated',
            ['since' => new Initializer(InitializerVariant::Literal, '\'8.1\'')]
        );
        self::assertTrue($expected->isSame($attribute));
    }

    public function test_attribute_from_reflection_data_int_arg(): void
    {
        $stub = <<<'STUB'
<?php
#[\Attr(int_one: 5, int_two: -10)]
const SOME_CONST = 5;
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $reflectionData = $reflector->reflectConstant('SOME_CONST')->getAttributes();
        self::assertCount(1, $reflectionData);
        $attribute = AttributeMetaData::fromReflectionData($reflectionData[0]);
        self::assertSame('\\Attr', $attribute->name);
        self::assertCount(2, $attribute->arguments);
        self::assertArrayHasKey('int_one', $attribute->arguments);
        self::assertInstanceOf(Initializer::class, $attribute->arguments['int_one']);
        self::assertSame(InitializerVariant::Literal, $attribute->arguments['int_one']->variant);
        self::assertSame('5', $attribute->arguments['int_one']->value);

        self::assertArrayHasKey('int_two', $attribute->arguments);
        self::assertInstanceOf(Initializer::class, $attribute->arguments['int_two']);
        self::assertSame(InitializerVariant::Literal, $attribute->arguments['int_two']->variant);
        self::assertSame('-10', $attribute->arguments['int_two']->value);

        $expected = new AttributeMetaData(
            '\\Attr',
            [
                'int_one' => new Initializer(InitializerVariant::Literal, '5'),
                'int_two' => new Initializer(InitializerVariant::Literal, '-10'),
            ]
        );
        self::assertTrue($expected->isSame($attribute));
    }

    public function test_attribute_from_reflection_data_int_float(): void
    {
        $stub = <<<'STUB'
<?php
#[\Attr(f_one: 5.4, f_two: -6.7, f_three: 1e2, f_four: -2e5)]
const SOME_CONST = 5;
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $reflectionData = $reflector->reflectConstant('SOME_CONST')->getAttributes();
        self::assertCount(1, $reflectionData);
        $attribute = AttributeMetaData::fromReflectionData($reflectionData[0]);
        self::assertSame('\\Attr', $attribute->name);
        self::assertCount(4, $attribute->arguments);
        self::assertArrayHasKey('f_one', $attribute->arguments);
        self::assertInstanceOf(Initializer::class, $attribute->arguments['f_one']);
        self::assertSame(InitializerVariant::Literal, $attribute->arguments['f_one']->variant);
        self::assertSame('5.4', $attribute->arguments['f_one']->value);

        self::assertArrayHasKey('f_two', $attribute->arguments);
        self::assertInstanceOf(Initializer::class, $attribute->arguments['f_two']);
        self::assertSame(InitializerVariant::Literal, $attribute->arguments['f_two']->variant);
        self::assertSame('-6.7', $attribute->arguments['f_two']->value);

        self::assertArrayHasKey('f_three', $attribute->arguments);
        self::assertInstanceOf(Initializer::class, $attribute->arguments['f_three']);
        self::assertSame(InitializerVariant::Literal, $attribute->arguments['f_three']->variant);
        self::assertSame('1e2', $attribute->arguments['f_three']->value);

        self::assertArrayHasKey('f_four', $attribute->arguments);
        self::assertInstanceOf(Initializer::class, $attribute->arguments['f_four']);
        self::assertSame(InitializerVariant::Literal, $attribute->arguments['f_four']->variant);
        self::assertSame('-2e5', $attribute->arguments['f_four']->value);

        $expected = new AttributeMetaData(
            '\\Attr',
            [
                'f_one' => new Initializer(InitializerVariant::Literal, '5.4'),
                'f_two' => new Initializer(InitializerVariant::Literal, '-6.7'),
                'f_three' => new Initializer(InitializerVariant::Literal, '1e2'),
                'f_four' => new Initializer(InitializerVariant::Literal, '-2e5'),
            ]
        );
        self::assertTrue($expected->isSame($attribute));
    }
}
