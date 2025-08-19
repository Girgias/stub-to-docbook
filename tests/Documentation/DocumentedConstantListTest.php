<?php

namespace Documentation;

use Dom\XMLDocument;
use Girgias\StubToDocbook\Documentation\DocumentedConstantList;
use Girgias\StubToDocbook\Documentation\DocumentedConstantListType;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/src/utils.php';

class DocumentedConstantListTest extends TestCase
{
    public function testVarEntryList(): void
    {
        $document = XMLDocument::createEmpty();
        $constants = [
            "HELLO" => new ConstantMetaData(
                "HELLO",
                new SingleType('string'),
                'UNKNOWN',
                'constant.hello',
                description: $document->createTextNode('description')
            ),
            "SOME_CONSTANT" => new ConstantMetaData(
                "SOME_CONSTANT",
                new SingleType('int'),
                'UNKNOWN',
                'constant.some-constant',
                description: $document->createTextNode('description')
            ),
        ];

        $list = new DocumentedConstantList($constants, DocumentedConstantListType::VarEntryList);

        $expectedXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<variablelist>
  <varlistentry xml:id="constant.hello">
    <term>
      <constant>HELLO</constant>
      (<type>string</type>)
    </term>
    <listitem>description</listitem>
  </varlistentry>
  <varlistentry xml:id="constant.some-constant">
    <term>
      <constant>SOME_CONSTANT</constant>
      (<type>int</type>)
    </term>
    <listitem>description</listitem>
  </varlistentry>
</variablelist>
XML;

        $xmlListElement = $list->generateXmlList($document, 0);
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
