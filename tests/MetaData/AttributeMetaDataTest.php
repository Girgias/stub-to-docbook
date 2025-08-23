<?php

namespace MetaData;

use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\InitializerVariant;
use PHPUnit\Framework\TestCase;

class AttributeMetaDataTest extends TestCase
{
    public function test_attribute_parsing(): void
    {
        $document = XMLDocument::createFromString('<modifier role="attribute">#[\Deprecated]</modifier>');
        $attribute = AttributeMetaData::parseFromDoc($document->firstElementChild);
        self::assertSame('\\Deprecated', $attribute->name);
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
}
