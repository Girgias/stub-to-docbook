<?php

namespace Documentation;

use Girgias\StubToDocbook\Documentation\DocumentedAttribute;
use PHPUnit\Framework\TestCase;

class DocumentedAttributeTest extends TestCase
{
    public function test_attribute_parsing(): void
    {
        $document = new \DOMDocument();
        $document->loadXML('<modifier role="attribute">#[\Deprecated]</modifier>');
        $attribute = DocumentedAttribute::parseFromDoc($document->firstChild);
        self::assertSame('\\Deprecated', $attribute->name);
    }
}
