<?php

namespace Documentation;

use Dom\XMLDocument;
use Girgias\StubToDocbook\Documentation\AttributeMetaData;
use PHPUnit\Framework\TestCase;

class AttributeMetaDataTest extends TestCase
{
    public function test_attribute_parsing(): void
    {
        $document = XMLDocument::createFromString('<modifier role="attribute">#[\Deprecated]</modifier>');
        $attribute = AttributeMetaData::parseFromDoc($document->firstElementChild);
        self::assertSame('\\Deprecated', $attribute->name);
    }
}
