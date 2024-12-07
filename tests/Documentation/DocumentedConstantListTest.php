<?php


namespace Documentation;

use DOMDocument;
use Girgias\StubToDocbook\Documentation\DocumentedConstant;
use Girgias\StubToDocbook\Documentation\DocumentedConstantList;
use Girgias\StubToDocbook\Documentation\DocumentedConstantListType;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/src/utils.php';

class DocumentedConstantListTest extends TestCase
{
    public function testVarEntryList(): void
    {
        $document = new DOMDocument();
        $constants = [
            "HELLO" => new DocumentedConstant("HELLO", new SingleType('string'), $document->createTextNode('description')),
            "SOME_CONSTANT" => new DocumentedConstant("SOME_CONSTANT", new SingleType('int'), $document->createTextNode('description'))
        ];

        $list = new DocumentedConstantList(DocumentedConstantListType::VarEntryList, $constants);

        $expectedXml = <<<'XML'
<?xml version="1.0"?>
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

        Assert::assertXmlStringEqualsXmlString($expectedXml, $document->saveXML());
        Assert::assertEquals($expectedXml, $document->saveXML());
    }

}
