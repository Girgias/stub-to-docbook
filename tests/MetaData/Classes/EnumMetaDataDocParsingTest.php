<?php

namespace MetaData\Classes;

use Dom\Element;
use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\Classes\EnumMetaData;
use PHPUnit\Framework\TestCase;

class EnumMetaDataDocParsingTest extends TestCase
{
    private const BACKED_ENUM_XML = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<classsynopsis class="enum" xmlns="http://docbook.org/ns/docbook">
 <ooclass>
  <enumname>TestEnum</enumname>
 </ooclass>

 <classsynopsisinfo role="comment">Backed by <type>string</type></classsynopsisinfo>

 <classsynopsisinfo role="comment">Cases</classsynopsisinfo>
 <fieldsynopsis>
  <varname>Foo</varname>
  <initializer>'foo'</initializer>
 </fieldsynopsis>
 <fieldsynopsis>
  <varname>Bar</varname>
  <initializer>'bar'</initializer>
 </fieldsynopsis>

 <classsynopsisinfo role="comment">Methods</classsynopsisinfo>
 <methodsynopsis role="TestEnum">
  <type>string</type><methodname>TestEnum::label</methodname>
  <void/>
 </methodsynopsis>
</classsynopsis>
XML;

    private const UNIT_ENUM_XML = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<classsynopsis class="enum" xmlns="http://docbook.org/ns/docbook">
 <ooclass>
  <enumname>Suit</enumname>
 </ooclass>

 <oointerface>
  <interfacename>HasColor</interfacename>
 </oointerface>

 <classsynopsisinfo role="comment">Cases</classsynopsisinfo>
 <fieldsynopsis>
  <varname>Hearts</varname>
 </fieldsynopsis>
 <fieldsynopsis>
  <varname>Diamonds</varname>
 </fieldsynopsis>
 <fieldsynopsis>
  <varname>Clubs</varname>
 </fieldsynopsis>
 <fieldsynopsis>
  <varname>Spades</varname>
 </fieldsynopsis>
</classsynopsis>
XML;

    private function loadElement(string $xml): Element
    {
        $doc = XMLDocument::createFromString($xml);
        $root = $doc->firstElementChild;
        self::assertInstanceOf(Element::class, $root);
        return $root;
    }

    public function test_parse_backed_enum(): void
    {
        $element = $this->loadElement(self::BACKED_ENUM_XML);
        $enum = EnumMetaData::parseFromDoc($element, 'test');

        self::assertSame('TestEnum', $enum->name);
        self::assertNotNull($enum->backingType);
        self::assertInstanceOf(\Girgias\StubToDocbook\Types\SingleType::class, $enum->backingType);
        self::assertSame('string', $enum->backingType->name);
        self::assertCount(2, $enum->cases);
        self::assertSame('Foo', $enum->cases[0]->name);
        self::assertSame("'foo'", $enum->cases[0]->value->value);
        self::assertSame('Bar', $enum->cases[1]->name);
        self::assertCount(1, $enum->methods);
        self::assertSame('test', $enum->extension);
    }

    public function test_parse_unit_enum_with_interface(): void
    {
        $element = $this->loadElement(self::UNIT_ENUM_XML);
        $enum = EnumMetaData::parseFromDoc($element, 'core');

        self::assertSame('Suit', $enum->name);
        self::assertNull($enum->backingType);
        self::assertCount(4, $enum->cases);
        self::assertSame('Hearts', $enum->cases[0]->name);
        self::assertNull($enum->cases[0]->value);
        self::assertSame('Spades', $enum->cases[3]->name);
        self::assertCount(1, $enum->implements);
        self::assertSame('HasColor', $enum->implements[0]);
        self::assertCount(0, $enum->methods);
    }
}
