<?php

namespace Documentation;

use Girgias\StubToDocbook\Documentation\DocumentedConstant;
use PHPUnit\Framework\TestCase;

class DocumentedConstantTest extends TestCase
{
    public function test_varlistentry_constant_parsing_all_data(): void
    {
        $xml = <<<'XML'
<varlistentry xml:id="constant.stdout">
 <term>
  <constant>STDOUT</constant>
  (<type>resource</type>)
 </term>
 <listitem>
  <simpara>
  An already opened stream to <literal>stdout</literal>.
   Available only under the CLI SAPI.
  </simpara>
 </listitem>
</varlistentry>
XML;
        $document = new \DOMDocument();
        $document->loadXML($xml);
        $constant = DocumentedConstant::parseFromVarListEntryTag($document->firstChild);

        self::assertSame('constant.stdout', $constant->id);
        self::assertSame('STDOUT', $constant->name);
        self::assertSame('resource', $constant->type);
    }

    public function test_varlistentry_constant_parsing_missing_type(): void
    {
        $xml = <<<'XML'
<varlistentry xml:id="constant.stdout">
 <term>
  <constant>STDOUT</constant>
 </term>
 <listitem>
  <simpara>
  An already opened stream to <literal>stdout</literal>.
   Available only under the CLI SAPI.
  </simpara>
 </listitem>
</varlistentry>
XML;
        $document = new \DOMDocument();
        $document->loadXML($xml);
        $constant = DocumentedConstant::parseFromVarListEntryTag($document->firstChild);

        self::assertSame('constant.stdout', $constant->id);
        self::assertSame('STDOUT', $constant->name);
        self::assertSame('MISSING', $constant->type);
    }

    public function test_varlistentry_constant_parsing_missing_linkage_id(): void
    {
        $xml = <<<'XML'
<varlistentry>
 <term>
  <constant>NAME</constant>
  (<type>T</type>)
 </term>
 <listitem>
  <simpara>
  An already opened stream to <literal>stdout</literal>.
   Available only under the CLI SAPI.
  </simpara>
 </listitem>
</varlistentry>
XML;
        $document = new \DOMDocument();
        $document->loadXML($xml);
        $constant = DocumentedConstant::parseFromVarListEntryTag($document->firstChild);

        self::assertNull($constant->id);
        self::assertSame('NAME', $constant->name);
        self::assertSame('T', $constant->type);
    }
}
