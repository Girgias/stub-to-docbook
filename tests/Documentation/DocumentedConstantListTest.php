<?php


namespace Documentation;

use Dom\XMLDocument;
use Girgias\StubToDocbook\Documentation\DocumentedConstant;
use Girgias\StubToDocbook\Documentation\DocumentedConstantList;
use Girgias\StubToDocbook\Documentation\DocumentedConstantListType;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/src/utils.php';

class DocumentedConstantListTest extends TestCase
{
    public function testVarEntryList(): void
    {
        $document = XMLDocument::createEmpty();
        $constants = [
            "HELLO" => new DocumentedConstant("HELLO", new SingleType('string'), $document->createTextNode('description')),
            "SOME_CONSTANT" => new DocumentedConstant("SOME_CONSTANT", new SingleType('int'), $document->createTextNode('description'))
        ];

        $list = new DocumentedConstantList(DocumentedConstantListType::VarEntryList, $constants);

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

        self::assertXmlStringEqualsXmlString($expectedXml, $document->saveXML());
        self::assertEquals($expectedXml, $document->saveXML());
    }

}
