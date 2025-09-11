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
        self::assertSame('8.1', $attribute->arguments['since']->value);

        $expected = new AttributeMetaData(
            '\\Deprecated',
            ['since' => new Initializer(InitializerVariant::Literal, '8.1')]
        );
        self::assertTrue($expected->isSame($attribute));
    }
}
