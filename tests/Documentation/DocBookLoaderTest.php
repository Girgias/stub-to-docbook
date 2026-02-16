<?php

namespace Documentation;

use Dom\XMLDocument;
use Girgias\StubToDocbook\Documentation\DocBookLoader;
use PHPUnit\Framework\TestCase;

class DocBookLoaderTest extends TestCase
{
    public function test_known_entities_are_replaced(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>'
            . '<root xmlns="http://docbook.org/ns/docbook">'
            . '<para>Value is &true; or &false; or &null;</para>'
            . '</root>';

        $doc = DocBookLoader::loadString($xml);

        $constants = $doc->getElementsByTagName('constant');
        self::assertSame(3, $constants->length);
        self::assertSame('true', $constants->item(0)->textContent);
        self::assertSame('false', $constants->item(1)->textContent);
        self::assertSame('null', $constants->item(2)->textContent);
    }

    public function test_unknown_entities_are_escaped(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>'
            . '<root xmlns="http://docbook.org/ns/docbook">'
            . '<para>&reftitle.description;</para>'
            . '</root>';

        $doc = DocBookLoader::loadString($xml);

        self::assertInstanceOf(XMLDocument::class, $doc);
        $xml = $doc->saveXml();
        self::assertIsString($xml);
        self::assertStringContainsString('&amp;reftitle.description;', $xml);
    }

    public function test_load_file_with_nonexistent_path(): void
    {
        $this->expectException(\RuntimeException::class);
        DocBookLoader::loadFile('/nonexistent/path.xml');
    }
}
