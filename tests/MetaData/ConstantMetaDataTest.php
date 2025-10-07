<?php

namespace MetaData;

use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Initializer;
use Girgias\StubToDocbook\MetaData\InitializerVariant;
use Girgias\StubToDocbook\MetaData\Lists\ConstantList;
use Girgias\StubToDocbook\MetaData\Visibility;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Girgias\StubToDocbook\Tests\ZendEngineStringSourceLocator;
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
#[\SomeAttribute(param: 'value')]
const E_PARSE = UNKNOWN;

/** @var int */
const CRYPT_STD_DES = 1;

/**
 * @var int
 * @deprecated
 */
const DEPRECATED_PHP_DOC = UNKNOWN;

/**
 * @var int
 */
#[\Deprecated(since: '8.1')]
const DEPRECATED_PHP_ATTRIBUTE = UNKNOWN;
STUB;

    public function test_can_retrieve_constants(): void
    {
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator(self::STUB_FILE_STR, $astLocator),
        ]);
        $constants = $reflector->reflectAllConstants();
        $constants = ConstantList::fromReflectionDataArray($constants)->constants;

        self::assertCount(6, $constants);

        self::assertArrayHasKey('E_ERROR', $constants);
        self::assertConstantIsSame($constants['E_ERROR'], 'E_ERROR', new SingleType('int'));
        self::assertFalse($constants['E_ERROR']->isDeprecated);

        self::assertArrayHasKey('E_WARNING', $constants);
        self::assertConstantIsSame($constants['E_WARNING'], 'E_WARNING', new SingleType('int'));
        self::assertFalse($constants['E_WARNING']->isDeprecated);

        self::assertArrayHasKey('E_PARSE', $constants);
        self::assertConstantIsSame($constants['E_PARSE'], 'E_PARSE', new SingleType('int'));
        $expectedEparseAttribute = new AttributeMetaData('\SomeAttribute', ['param' => new Initializer(InitializerVariant::Literal, "'value'")]);
        self::assertCount(1, $constants['E_PARSE']->attributes);
        self::assertTrue($expectedEparseAttribute->isSame($constants['E_PARSE']->attributes[0]));
        self::assertFalse($constants['E_PARSE']->isDeprecated);

        self::assertArrayHasKey('CRYPT_STD_DES', $constants);
        self::assertConstantIsSame($constants['CRYPT_STD_DES'], 'CRYPT_STD_DES', new SingleType('int'));
        self::assertFalse($constants['CRYPT_STD_DES']->isDeprecated);

        self::assertArrayHasKey('DEPRECATED_PHP_DOC', $constants);
        self::assertConstantIsSame($constants['DEPRECATED_PHP_DOC'], 'DEPRECATED_PHP_DOC', new SingleType('int'));
        self::assertCount(0, $constants['DEPRECATED_PHP_DOC']->attributes);
        self::assertTrue($constants['DEPRECATED_PHP_DOC']->isDeprecated);

        self::assertArrayHasKey('DEPRECATED_PHP_ATTRIBUTE', $constants);
        self::assertConstantIsSame($constants['DEPRECATED_PHP_ATTRIBUTE'], 'DEPRECATED_PHP_ATTRIBUTE', new SingleType('int'));
        $expectedDeprecatedPhpAttribute = new AttributeMetaData('\Deprecated', ['since' => new Initializer(InitializerVariant::Literal, "'8.1'")]);
        self::assertCount(1, $constants['DEPRECATED_PHP_ATTRIBUTE']->attributes);
        self::assertTrue($expectedDeprecatedPhpAttribute->isSame($constants['DEPRECATED_PHP_ATTRIBUTE']->attributes[0]));
        self::assertTrue($constants['DEPRECATED_PHP_ATTRIBUTE']->isDeprecated);
    }

    private static function assertConstantIsSame(ConstantMetaData $constant, string $name, SingleType $type): void
    {
        self::assertSame($name, $constant->name);
        self::assertTrue($type->isSame($constant->type));
    }

    public function test_class_constants(): void
    {

        $stub = <<<'STUB'
<?php

/** @generate-class-entries */
class Foo {
    const int MY_CONST = UNKNOWN;
    protected const int PROTECTED_CONST = UNKNOWN;
    private const int PRIVATE_CONST = UNKNOWN;
    final const int FINAL_CONST = UNKNOWN;
}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new ZendEngineStringSourceLocator($stub, $astLocator),
        ]);

        $constants = array_map(
            ConstantMetaData::fromReflectionData(...),
            $reflector->reflectClass('foo')->getConstants()
        );

        self::assertCount(4, $constants);
        self::assertArrayHasKey('MY_CONST', $constants);
        self::assertSame(Visibility::Public, $constants['MY_CONST']->visibility);
        self::assertFalse($constants['MY_CONST']->isFinal);

        self::assertArrayHasKey('PROTECTED_CONST', $constants);
        self::assertSame(Visibility::Protected, $constants['PROTECTED_CONST']->visibility);
        self::assertFalse($constants['PROTECTED_CONST']->isFinal);

        self::assertArrayHasKey('PRIVATE_CONST', $constants);
        self::assertSame(Visibility::Private, $constants['PRIVATE_CONST']->visibility);
        self::assertFalse($constants['PRIVATE_CONST']->isFinal);

        self::assertArrayHasKey('FINAL_CONST', $constants);
        self::assertSame(Visibility::Public, $constants['FINAL_CONST']->visibility);
        self::assertTrue($constants['FINAL_CONST']->isFinal);
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
