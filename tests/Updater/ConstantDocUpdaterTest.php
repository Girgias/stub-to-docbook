<?php

namespace Updater;

use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\Types\SingleType;
use Girgias\StubToDocbook\Updater\ConstantDocUpdater;
use PHPUnit\Framework\TestCase;

class ConstantDocUpdaterTest extends TestCase
{
    public function test_update_type_in_varlistentry(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<varlistentry xml:id="constant.test" xmlns="http://docbook.org/ns/docbook">
 <term>
  <constant>TEST_CONST</constant>
  (<type>string</type>)
 </term>
 <listitem><simpara>Description</simpara></listitem>
</varlistentry>
XML;

        $doc = XMLDocument::createFromString($xml);
        $entry = $doc->firstElementChild;

        $stubConstant = new ConstantMetaData(
            'TEST_CONST',
            new SingleType('int'),
            'test',
            'constant.test',
        );

        $result = ConstantDocUpdater::updateTypeInVarListEntry($entry, $stubConstant);
        self::assertTrue($result);

        $output = $doc->saveXml($entry);
        self::assertStringContainsString('<type>int</type>', $output);
        self::assertStringNotContainsString('<type>string</type>', $output);
    }

    public function test_update_type_in_table_row(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<row xmlns="http://docbook.org/ns/docbook">
 <entry><constant>TABLE_CONST</constant></entry>
 <entry><type>string</type></entry>
 <entry>Description</entry>
</row>
XML;

        $doc = XMLDocument::createFromString($xml);
        $row = $doc->firstElementChild;

        $stubConstant = new ConstantMetaData(
            'TABLE_CONST',
            new SingleType('float'),
            'test',
            null,
        );

        $result = ConstantDocUpdater::updateTypeInTableRow($row, $stubConstant);
        self::assertTrue($result);

        $output = $doc->saveXml($row);
        self::assertStringContainsString('<type>float</type>', $output);
        self::assertStringNotContainsString('<type>string</type>', $output);
    }

    public function test_no_update_when_stub_has_no_type(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<varlistentry xmlns="http://docbook.org/ns/docbook">
 <term><constant>NO_TYPE</constant></term>
 <listitem><simpara>Description</simpara></listitem>
</varlistentry>
XML;

        $doc = XMLDocument::createFromString($xml);
        $entry = $doc->firstElementChild;

        $stubConstant = new ConstantMetaData(
            'NO_TYPE',
            null,
            'test',
            null,
        );

        $result = ConstantDocUpdater::updateTypeInVarListEntry($entry, $stubConstant);
        self::assertFalse($result);
    }
}
