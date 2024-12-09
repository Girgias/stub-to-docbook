<?php

namespace Documentation;

use Dom\XMLDocument;
use Girgias\StubToDocbook\Documentation\DocumentedAttribute;
use PHPUnit\Framework\TestCase;

class DocumentedAttributeTest extends TestCase
{
    public function test_attribute_parsing(): void
    {
        $document = XMLDocument::createFromString('<modifier role="attribute">#[\Deprecated]</modifier>');
        $attribute = DocumentedAttribute::parseFromDoc($document->firstElementChild);
        self::assertSame('\\Deprecated', $attribute->name);
    }
}
