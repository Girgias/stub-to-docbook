<?php

namespace Documentation\Functions;

use Dom\Text;
use Dom\XMLDocument;
use Girgias\StubToDocbook\Documentation\AttributeMetaData;
use Girgias\StubToDocbook\Documentation\Functions\ParameterMetaData;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;

class ParameterMetaDataTest extends TestCase
{
    private static function expected_param(mixed ...$entries): ParameterMetaData {
        return new ParameterMetaData('param_name', 1, new SingleType('string'), ...$entries);
    }

    public function test_basic_parameter_parsing(): void
    {
        $xml = '<methodparam><type>string</type><parameter>param_name</parameter></methodparam>';
        $document = XMLDocument::createFromString($xml);
        $param = ParameterMetaData::parseFromMethodParamDocTag($document->firstElementChild, 1);

        self::assertTrue($param->isSame(self::expected_param()));
    }

    public function test_by_ref_parameter_parsing(): void
    {
        $xml = '<methodparam><type>string</type><parameter role="reference">param_name</parameter></methodparam>';
        $document = XMLDocument::createFromString($xml);
        $param = ParameterMetaData::parseFromMethodParamDocTag($document->firstElementChild, 1);

        self::assertTrue($param->isSame(self::expected_param(
            isByRef: true,
        )));
    }

    public function test_parameter_parsing_has_attribute(): void
    {
        $xml = '<methodparam><modifier role="attribute">#[\SensitiveParameter]</modifier><type>string</type><parameter>param_name</parameter></methodparam>';
        $document = XMLDocument::createFromString($xml);
        $param = ParameterMetaData::parseFromMethodParamDocTag($document->firstElementChild, 1);

        self::assertTrue($param->isSame(self::expected_param(
            attributes: [new AttributeMetaData('\SensitiveParameter')],
        )));
        self::assertCount(1, $param->attributes);
    }

    public function test_variadic_parameter_parsing(): void
    {
        $xml = '<methodparam rep="repeat"><type>string</type><parameter>param_name</parameter></methodparam>';
        $document = XMLDocument::createFromString($xml);
        $param = ParameterMetaData::parseFromMethodParamDocTag($document->firstElementChild, 1);

        self::assertTrue($param->isSame(self::expected_param(
            isVariadic: true,
        )));
    }

    public function test_option_parameter_parsing_no_initializer(): void
    {
        $xml = '<methodparam choice="opt"><type>string</type><parameter>param_name</parameter></methodparam>';
        $document = XMLDocument::createFromString($xml);
        $param = ParameterMetaData::parseFromMethodParamDocTag($document->firstElementChild, 1);

        self::assertTrue($param->isSame(self::expected_param(
            isOptional: true,
            defaultValue: null,
        )));
    }

    public function test_option_parameter_parsing_with_initializer(): void
    {
        $xml = '<methodparam choice="opt"><type>string</type><parameter>param_name</parameter><initializer><constant>SOME_CONST</constant></initializer></methodparam>';
        $document = XMLDocument::createFromString($xml);
        $param = ParameterMetaData::parseFromMethodParamDocTag($document->firstElementChild, 1);

        self::assertTrue($param->isSame(self::expected_param(
            isOptional: true,
            // TODO Parsing of initializer tag is less than ideal.
            defaultValue: 'SOME_CONST',
        )));
    }

    public function test_var_list_entry_parameter_parsing(): void
    {
        $xml = <<<'XML'
<varlistentry xmlns="http://docbook.org/ns/docbook">
 <term><parameter>param_name</parameter></term>
 <listitem>
  <simpara>
   Description.
  </simpara>
</listitem>
</varlistentry>
XML;
        $document = XMLDocument::createFromString($xml);
        $param = ParameterMetaData::parseFromVaListEntryDocTag($document->firstElementChild, 1);

        self::assertTrue($param->isSame(new ParameterMetaData(
          'param_name',
            1,
            new SingleType('UNKNOWN'),
        )));
    }

    public function test_var_list_entry_parameter_parsing_no_param(): void
    {
        $xml = <<<'XML'
<varlistentry xmlns="http://docbook.org/ns/docbook">
 <term>oops</term>
 <listitem>
  <simpara>
   Description.
  </simpara>
</listitem>
</varlistentry>
XML;
        $document = XMLDocument::createFromString($xml);

        try {
            $param = ParameterMetaData::parseFromVaListEntryDocTag($document->firstElementChild, 1);
        } catch (\Throwable $exception) {
            self::assertSame('Unexpected missing <term><parameter> tag sequence', $exception->getMessage());
        }
    }

    public function test_var_list_entry_parameter_parsing_multiple_term_param(): void
    {
        $xml = <<<'XML'
<varlistentry xmlns="http://docbook.org/ns/docbook">
 <term><parameter>param_name</parameter></term>
 <term><parameter>param_name2</parameter></term>
 <listitem>
  <simpara>
   Description.
  </simpara>
</listitem>
</varlistentry>
XML;
        $document = XMLDocument::createFromString($xml);

        try {
            $param = ParameterMetaData::parseFromVaListEntryDocTag($document->firstElementChild, 1);
        } catch (\Throwable $exception) {
            self::assertSame('Unexpected multiple <term><parameter> tag sequences', $exception->getMessage());
        }
    }

    public function test_var_list_entries_parameter_parsing_no_side_effects(): void
    {
        $xml = <<<'XML'
<variablelist xmlns="http://docbook.org/ns/docbook">
 <varlistentry>
  <term><parameter>param1</parameter></term>
  <listitem>
   <simpara>
    Description.
   </simpara>
 </listitem>
 </varlistentry>
 <varlistentry>
  <term><parameter>param2</parameter></term>
  <listitem>
   <simpara>
    Description.
   </simpara>
 </listitem>
 </varlistentry>
</variablelist>
XML;
        $document = XMLDocument::createFromString($xml);
        $variableList = $document->firstElementChild;

        $params = [];
        $position = 1;
        foreach ($variableList->childNodes as $variableEntry) {
            if ($variableEntry instanceof Text) {
                continue;
            }
            $params[] = ParameterMetaData::parseFromVaListEntryDocTag($variableEntry, $position);
            $position++;
        }

        self::assertCount(2, $params);
        self::assertTrue($params[0]->isSame(new ParameterMetaData(
            'param1',
            1,
            new SingleType('UNKNOWN'),
        )));
        self::assertTrue($params[1]->isSame(new ParameterMetaData(
            'param2',
            2,
            new SingleType('UNKNOWN'),
        )));
    }
}
