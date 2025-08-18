<?php

namespace MetaData;

use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\Stubs\StubConstantList;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

class ConstantMetaDataTest extends TestCase
{
    const /* string */ STUB_FILE_STR = <<<'STUB'
<?php

/** @generate-class-entries */

/**
 * @var int
 * @cvalue E_ERROR
 */
const E_ERROR = UNKNOWN;

/**
 * @var int
 * @cvalue E_WARNING
 */
const E_WARNING = UNKNOWN;

/**
 * @var int
 * @cvalue E_PARSE
 */
const E_PARSE = UNKNOWN;

/** @var int */
const CRYPT_STD_DES = 1;
STUB;

    public function test_can_retrieve_constants(): void
    {
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator(self::STUB_FILE_STR, $astLocator),
        ]);
        $constants = $reflector->reflectAllConstants();
        $constants = StubConstantList::fromReflectionDataArray($constants)->constants;

        self::assertCount(4, $constants);
        self::assertArrayHasKey('E_ERROR', $constants);
        self::assertConstantIsSame($constants['E_ERROR'], 'E_ERROR', new SingleType('int'));
        self::assertArrayHasKey('E_WARNING', $constants);
        self::assertConstantIsSame($constants['E_WARNING'], 'E_WARNING', new SingleType('int'));
        self::assertArrayHasKey('E_PARSE', $constants);
        self::assertConstantIsSame($constants['E_PARSE'], 'E_PARSE', new SingleType('int'));
        self::assertArrayHasKey('CRYPT_STD_DES', $constants);
        self::assertConstantIsSame($constants['CRYPT_STD_DES'], 'CRYPT_STD_DES', new SingleType('int'));
    }

    private static function assertConstantIsSame(ConstantMetaData $constant, string $name, SingleType $type): void
    {
        self::assertSame($name, $constant->name);
        self::assertTrue($type->isSame($constant->type));
    }

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
        $document = XMLDocument::createFromString($xml);
        $constant = ConstantMetaData::parseFromVarListEntryTag($document->firstElementChild, 'UNKNOWN');

        $expectedType = new SingleType('resource');

        self::assertSame('constant.stdout', $constant->id);
        self::assertSame('STDOUT', $constant->name);
        self::assertTrue($expectedType->isSame($constant->type));
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
        $document = XMLDocument::createFromString($xml);
        $constant = ConstantMetaData::parseFromVarListEntryTag($document->firstElementChild, 'UNKNOWN');

        self::assertSame('constant.stdout', $constant->id);
        self::assertSame('STDOUT', $constant->name);
        self::assertNull($constant->type);
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
        $document = XMLDocument::createFromString($xml);
        $constant = ConstantMetaData::parseFromVarListEntryTag($document->firstElementChild, 'UNKNOWN');

        $expectedType = new SingleType('T');

        self::assertNull($constant->id);
        self::assertSame('NAME', $constant->name);
        self::assertTrue($expectedType->isSame($constant->type));
    }

    public function test_to_varlistentry_xml_no_description(): void
    {
        $expectedXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<varlistentry xml:id="constant.some-constant">
  <term>
    <constant>SOME_CONSTANT</constant>
    (<type>int</type>)
  </term>
  <listitem>
    <simpara>Description</simpara>
  </listitem>
</varlistentry>
XML;

        $constant = new ConstantMetaData(
            'SOME_CONSTANT',
            new SingleType('int'),
            'UNKNOWN',
            'constant.some-constant',
        );

        $document = XMLDocument::createEmpty();
        $varEntryList = $constant->toVarListEntryXml($document, 0);
        $document->append($varEntryList);
        $savedXml = $document->saveXML();
        self::assertXmlStringEqualsXmlString($expectedXml, $savedXml);

        $document->formatOutput = true;
        $savedXml = $document->saveXML();
        self::assertSame($expectedXml, $savedXml);
    }

    public function test_to_varlistentry_xml_no_id(): void
    {
        $expectedXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<varlistentry>
  <term>
    <constant>SOME_CONSTANT</constant>
    (<type>int</type>)
  </term>
  <listitem>
    <simpara>Description</simpara>
  </listitem>
</varlistentry>
XML;

        $constant = new ConstantMetaData(
            'SOME_CONSTANT',
            new SingleType('int'),
            'UNKNOWN',
            null,
        );

        $document = XMLDocument::createEmpty();
        $varEntryList = $constant->toVarListEntryXml($document, 0);
        $document->append($varEntryList);
        $savedXml = $document->saveXML();
        self::assertXmlStringEqualsXmlString($expectedXml, $savedXml);

        $document->formatOutput = true;
        $savedXml = $document->saveXML();
        self::assertSame($expectedXml, $savedXml);
    }
}
