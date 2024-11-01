<?php


namespace Documentation;

use DOMDocument;
use Girgias\StubToDocbook\Documentation\DocumentedConstant;
use Girgias\StubToDocbook\Documentation\DocumentedConstantList;
use Girgias\StubToDocbook\Documentation\DocumentedConstantListType;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/src/utils.php';

class DocumentedConstantListTest extends TestCase
{
    public function testVarEntryList(): void
    {
        $document = new DOMDocument();
        $constants = [
            new DocumentedConstant("HELLO", 'string', $document->createTextNode('description')),
            new DocumentedConstant("SOME_CONSTANT", 'int', $document->createTextNode('description'))
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
