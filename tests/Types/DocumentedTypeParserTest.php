<?php

namespace Types;

use Girgias\StubToDocbook\Types\DocumentedTypeParser;
use Girgias\StubToDocbook\Types\IntersectionType;
use Girgias\StubToDocbook\Types\SingleType;
use Girgias\StubToDocbook\Types\UnionType;
use PHPUnit\Framework\TestCase;

class DocumentedTypeParserTest extends TestCase
{
    public function test_parsing_simple_type_tag(): void
    {
        $document = new \DOMDocument();
        $document->loadXML('<type>string</type>');
        $type = DocumentedTypeParser::parse($document->firstChild);

        $expectedType = new SingleType('string');
        self::assertTrue($expectedType->isSame($type));
    }

    public function test_parsing_simple_union_type_tag(): void
    {
        $document = new \DOMDocument();
        $document->loadXML('<type class="union"><type>Countable</type><type>array</type></type>');
        $type = DocumentedTypeParser::parse($document->firstChild);

        $expectedType = new UnionType([
            new SingleType('Countable'),
            new SingleType('array'),
        ]);

        self::assertTrue($expectedType->isSame($type));
    }

    public function test_parsing_simple_intersection_type_tag(): void
    {
        $document = new \DOMDocument();
        $document->loadXML('<type class="intersection"><type>X</type><type>Y</type></type>');
        $type = DocumentedTypeParser::parse($document->firstChild);

        $expectedType = new IntersectionType([
            new SingleType('X'),
            new SingleType('Y'),
        ]);

        self::assertTrue($expectedType->isSame($type));
    }

    public function test_parsing_dnf_type_tag(): void
    {

        $xml = '<type class="union"><type class="intersection"><type>A</type><type>B</type></type><type class="intersection"><type>X</type><type>Y</type></type><type>array</type></type>';
        $document = new \DOMDocument();
        $document->loadXML($xml);
        $type = DocumentedTypeParser::parse($document->firstChild);

        $expectedType = new UnionType([
            new IntersectionType([
                new SingleType('A'),
                new SingleType('B'),
            ]),
            new IntersectionType([
                new SingleType('X'),
                new SingleType('Y'),
            ]),
            new SingleType('array'),
        ]);

        self::assertTrue($expectedType->isSame($type));
    }
}
