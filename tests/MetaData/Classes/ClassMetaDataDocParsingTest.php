<?php

namespace MetaData\Classes;

use Dom\Element;
use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\Classes\ClassMetaData;
use PHPUnit\Framework\TestCase;

class ClassMetaDataDocParsingTest extends TestCase
{
    private const CLASS_XML = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<classsynopsis class="class" xmlns="http://docbook.org/ns/docbook">
 <ooclass>
  <modifier>final</modifier>
  <classname>TestClass</classname>
 </ooclass>

 <oointerface>
  <interfacename>Stringable</interfacename>
 </oointerface>

 <classsynopsisinfo role="comment">Constants</classsynopsisinfo>
 <fieldsynopsis>
  <modifier>public</modifier>
  <modifier>const</modifier>
  <type>int</type>
  <constant>TestClass::MY_CONST</constant>
  <initializer>42</initializer>
 </fieldsynopsis>
 <fieldsynopsis>
  <modifier>protected</modifier>
  <modifier>const</modifier>
  <type>string</type>
  <constant>TestClass::SECRET</constant>
  <initializer>'hidden'</initializer>
 </fieldsynopsis>

 <classsynopsisinfo role="comment">Properties</classsynopsisinfo>
 <fieldsynopsis>
  <modifier>public</modifier>
  <type>string</type>
  <varname>name</varname>
 </fieldsynopsis>

 <classsynopsisinfo role="comment">Methods</classsynopsisinfo>
 <methodsynopsis role="TestClass">
  <type>string</type><methodname>TestClass::getName</methodname>
  <void/>
 </methodsynopsis>
</classsynopsis>
XML;

    private function loadElement(string $xml): Element
    {
        $doc = XMLDocument::createFromString($xml);
        $root = $doc->firstElementChild;
        self::assertInstanceOf(Element::class, $root);
        return $root;
    }

    public function test_parse_class_with_constants(): void
    {
        $element = $this->loadElement(self::CLASS_XML);
        $class = ClassMetaData::parseFromDoc($element, 'test');

        self::assertSame('TestClass', $class->name);
        self::assertTrue($class->isFinal);
        self::assertFalse($class->isAbstract);

        // Constants
        self::assertCount(2, $class->constants);
        self::assertSame('MY_CONST', $class->constants[0]->name);
        self::assertSame('int', $class->constants[0]->type->name);
        self::assertSame('SECRET', $class->constants[1]->name);

        // Properties
        self::assertCount(1, $class->properties);
        self::assertSame('name', $class->properties[0]->name);

        // Methods
        self::assertCount(1, $class->methods);

        // Interfaces
        self::assertCount(1, $class->implements);
        self::assertSame('Stringable', $class->implements[0]);
    }
}
