<?php

namespace MetaData\Lists;

use Dom\XMLDocument;
use Girgias\StubToDocbook\Documentation\DocumentedConstantListType;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Lists\ConstantList;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;

class ConstantListTest extends TestCase
{
    public function test_to_xml_varlistentry_list()
    {
        $document = XMLDocument::createEmpty();
        $constants = [
            "HELLO" => new ConstantMetaData(
                "HELLO",
                new SingleType('string'),
                'UNKNOWN',
                'constant.hello',
                description: $document->createTextNode('The hello constant')
            ),
            "SOME_CONSTANT" => new ConstantMetaData(
                "SOME_CONSTANT",
                new SingleType('int'),
                'UNKNOWN',
                'constant.some-constant',
                description: $document->createTextNode('A constant that does something')
            ),
        ];
        $constantList = new ConstantList($constants, DocumentedConstantListType::VarEntryList);

        $expectedXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<variablelist>
  <varlistentry xml:id="constant.hello">
    <term>
      <constant>HELLO</constant>
      (<type>string</type>)
    </term>
    <listitem>The hello constant</listitem>
  </varlistentry>
  <varlistentry xml:id="constant.some-constant">
    <term>
      <constant>SOME_CONSTANT</constant>
      (<type>int</type>)
    </term>
    <listitem>A constant that does something</listitem>
  </varlistentry>
</variablelist>
XML;

        $xmlListElement = $constantList->toXml($document, 0);
        $document->append($xmlListElement);

        $savedXml = $document->saveXML();
        self::assertIsString($savedXml);
        self::assertXmlStringEqualsXmlString($expectedXml, $savedXml);

        $document->formatOutput = true;
        $savedXml = $document->saveXML();
        self::assertIsString($savedXml);
        self::assertSame($expectedXml, $savedXml);
        self::assertEquals($expectedXml, $document->saveXML());
    }
}
