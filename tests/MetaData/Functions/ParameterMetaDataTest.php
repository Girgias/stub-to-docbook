<?php

namespace MetaData\Functions;

use Dom\Element;
use Dom\Text;
use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\Functions\ParameterMetaData;
use Girgias\StubToDocbook\MetaData\Initializer;
use Girgias\StubToDocbook\MetaData\InitializerVariant;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Girgias\StubToDocbook\Types\SingleType;
use Girgias\StubToDocbook\Types\UnionType;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

class ParameterMetaDataTest extends TestCase
{
    public function test_optional_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php
/**
 * @compile-time-eval
 * @refcount 1
 */
function array_column(array $array, int|string|null $column_key, int|string|null $index_key = null): array {}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
            $reflectionFunction = $reflector->reflectFunction('array_column');
        $param = $reflectionFunction->getParameter('index_key');
        $param = ParameterMetaData::fromReflectionData($param);

        self::assertSame('index_key', $param->name);
        self::assertSame(3, $param->position);
        self::assertTrue(
            (new UnionType([
                new SingleType('int'),
                new SingleType('string'),
                new SingleType('null'),
            ]))->isSame($param->type),
        );
        self::assertTrue($param->isOptional);
        self::assertFalse($param->isVariadic);
        self::assertFalse($param->isByRef);
        self::assertSame([], $param->attributes);
        /* Default Value */
        $expectedDefaultValue = new Initializer(
            InitializerVariant::Constant,
            'null',
        );
        self::assertTrue($expectedDefaultValue->isSame($param->defaultValue));
    }

    public function test_variadic_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php
/**
 * @compile-time-eval
 */
function array_merge(array ...$arrays): array {}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectFunction('array_merge');
        $param = $reflectionFunction->getParameter('arrays');
        $param = ParameterMetaData::fromReflectionData($param);

        self::assertSame('arrays', $param->name);
        self::assertSame(1, $param->position);
        self::assertTrue((new SingleType('array'))->isSame($param->type));
        self::assertTrue($param->isVariadic);
        /* isVariadic => isOptional() however not sure we want those semantics */
        self::assertTrue($param->isOptional);
        self::assertFalse($param->isByRef);
        self::assertSame([], $param->attributes);
        self::assertNull($param->defaultValue);
    }

    public function test_type_in_doc_comment_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php
/**
 * @param string $type_doc
 */
function my_function($type_doc): array {}
STUB;
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectFunction('my_function');
        $param = $reflectionFunction->getParameter('type_doc');
        $param = ParameterMetaData::fromReflectionData($param);

        self::assertSame('type_doc', $param->name);
        self::assertSame(1, $param->position);
        self::assertTrue((new SingleType('string'))->isSame($param->type));
        self::assertFalse($param->isVariadic);
        /* isVariadic => isOptional() however not sure we want those semantics */
        self::assertFalse($param->isOptional);
        self::assertFalse($param->isByRef);
        self::assertSame([], $param->attributes);
        self::assertNull($param->defaultValue);
    }

    public function test_by_ref_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php
function sort(array &$array, int $flags = SORT_REGULAR): true {}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectFunction('sort');
        $param = $reflectionFunction->getParameter('array');
        $param = ParameterMetaData::fromReflectionData($param);

        self::assertSame('array', $param->name);
        self::assertSame(1, $param->position);
        self::assertTrue((new SingleType('array'))->isSame($param->type));
        self::assertTrue($param->isByRef);
        self::assertFalse($param->isVariadic);
        self::assertFalse($param->isOptional);
        self::assertSame([], $param->attributes);
        self::assertNull($param->defaultValue);
    }

    public function test_with_attribute_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php
/**
 * @refcount 1
 */
function hash_hmac(string $algo, string $data, #[\SensitiveParameter] string $key, bool $binary = false): string {}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectFunction('hash_hmac');
        $param = $reflectionFunction->getParameter('key');
        $param = ParameterMetaData::fromReflectionData($param);

        self::assertSame('key', $param->name);
        self::assertSame(3, $param->position);
        self::assertTrue((new SingleType('string'))->isSame($param->type));
        self::assertFalse($param->isByRef);
        self::assertFalse($param->isVariadic);
        self::assertFalse($param->isOptional);
        self::assertNull($param->defaultValue);
        self::assertCount(1, $param->attributes);
        self::assertTrue((new AttributeMetaData('\\SensitiveParameter'))->isSame($param->attributes[0]));
    }

    public function test_default_values_const_expr_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php
function test(
    null $a = null,
    bool $b = false,
    bool $c = true,
    int $d = 42,
    float $e = 25.6,
    string $f = 'hi',
    string $g = "Hello",
    array $h = [],
    array $i = [1, 2],
    array $j = [1 => 'uno', 2 => 'dos'],
    string $k = GLOBAL_CONST,
    string $l = SomeClass::CONST,
): void {}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectFunction('test');

        $params = array_map(
            ParameterMetaData::fromReflectionData(...),
            $reflectionFunction->getParameters(),
        );

        self::assertCount(12, $params);
        self::assertSame(InitializerVariant::Constant, $params[0]->defaultValue->variant);
        self::assertSame('null', $params[0]->defaultValue->value);
        self::assertSame(InitializerVariant::Constant, $params[1]->defaultValue->variant);
        self::assertSame('false', $params[1]->defaultValue->value);
        self::assertSame(InitializerVariant::Constant, $params[2]->defaultValue->variant);
        self::assertSame('true', $params[2]->defaultValue->value);
        self::assertSame(InitializerVariant::Literal, $params[3]->defaultValue->variant);
        self::assertSame('42', $params[3]->defaultValue->value);
        self::assertSame(InitializerVariant::Literal, $params[4]->defaultValue->variant);
        self::assertSame('25.6', $params[4]->defaultValue->value);
        self::assertSame(InitializerVariant::Literal, $params[5]->defaultValue->variant);
        self::assertSame('\'hi\'', $params[5]->defaultValue->value);
        self::assertSame(InitializerVariant::Literal, $params[6]->defaultValue->variant);
        self::assertSame('"Hello"', $params[6]->defaultValue->value);
        self::assertSame(InitializerVariant::Literal, $params[7]->defaultValue->variant);
        self::assertSame('[]', $params[7]->defaultValue->value);
        self::assertSame(InitializerVariant::Literal, $params[8]->defaultValue->variant);
        self::assertSame('[1, 2]', $params[8]->defaultValue->value);
        self::assertSame(InitializerVariant::Literal, $params[9]->defaultValue->variant);
        self::assertSame("[1 => 'uno', 2 => 'dos']", $params[9]->defaultValue->value);
        self::assertSame(InitializerVariant::Constant, $params[10]->defaultValue->variant);
        self::assertSame('GLOBAL_CONST', $params[10]->defaultValue->value);
        self::assertSame(InitializerVariant::Constant, $params[11]->defaultValue->variant);
        self::assertSame('SomeClass::CONST', $params[11]->defaultValue->value);
    }

    public function test_default_values_bitwise_expr_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php
function test(
    int $a = FLAG_A|FLAG_B,
    int $b = FLAG_A|FLAG_B|FLAG_C,
): void {}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectFunction('test');

        $params = array_map(
            ParameterMetaData::fromReflectionData(...),
            $reflectionFunction->getParameters(),
        );

        self::assertCount(2, $params);
        self::assertSame(InitializerVariant::BitMask, $params[0]->defaultValue->variant);
        self::assertSame('FLAG_A|FLAG_B', $params[0]->defaultValue->value);
        self::assertSame(InitializerVariant::BitMask, $params[1]->defaultValue->variant);
        self::assertSame('FLAG_A|FLAG_B|FLAG_C', $params[1]->defaultValue->value);
    }

    public function test_default_values_function_call_expr_from_reflection_data(): void
    {
        $stub = <<<'STUB'
<?php
function test(
    int $a = foo(),
    int $b = bar(5),
): void {}
STUB;

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator($stub, $astLocator),
        ]);
        $reflectionFunction = $reflector->reflectFunction('test');

        $params = array_map(
            ParameterMetaData::fromReflectionData(...),
            $reflectionFunction->getParameters(),
        );

        self::assertCount(2, $params);
        self::assertSame(InitializerVariant::Function, $params[0]->defaultValue->variant);
        self::assertSame('foo()', $params[0]->defaultValue->value);
        self::assertSame(InitializerVariant::Function, $params[1]->defaultValue->variant);
        self::assertSame('bar(5)', $params[1]->defaultValue->value);
    }

    private static function expected_param(mixed ...$entries): ParameterMetaData
    {
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
            attributes: [new AttributeMetaData('\\SensitiveParameter')],
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

    public function test_option_parameter_parsing_with_const_initializer(): void
    {
        $xml = '<methodparam choice="opt"><type>string</type><parameter>param_name</parameter><initializer><constant>SOME_CONST</constant></initializer></methodparam>';
        $document = XMLDocument::createFromString($xml);
        $param = ParameterMetaData::parseFromMethodParamDocTag($document->firstElementChild, 1);

        self::assertTrue($param->isSame(self::expected_param(
            isOptional: true,
            defaultValue: new Initializer(
                InitializerVariant::Constant,
                'SOME_CONST',
            ),
        )));
    }

    public function test_to_method_param_xml_basic(): void
    {
        $param = new ParameterMetaData('name', 1, new SingleType('string'));

        $document = XMLDocument::createEmpty();
        $element = $param->toMethodParamXml($document);
        $document->append($element);
        $xml = $document->saveXml($element);
        self::assertIsString($xml);

        self::assertSame('<methodparam><type>string</type><parameter>name</parameter></methodparam>', $xml);
    }

    public function test_to_method_param_xml_optional_with_default(): void
    {
        $param = new ParameterMetaData(
            'length',
            1,
            new UnionType([new SingleType('int'), new SingleType('null')]),
            isOptional: true,
            defaultValue: new Initializer(InitializerVariant::Literal, 'null'),
        );

        $document = XMLDocument::createEmpty();
        $element = $param->toMethodParamXml($document);
        $document->append($element);
        $xml = $document->saveXml($element);
        self::assertIsString($xml);

        self::assertStringContainsString('choice="opt"', $xml);
        self::assertStringContainsString('<initializer>null</initializer>', $xml);
    }

    public function test_to_method_param_xml_by_ref(): void
    {
        $param = new ParameterMetaData('array', 1, new SingleType('array'), isByRef: true);

        $document = XMLDocument::createEmpty();
        $element = $param->toMethodParamXml($document);
        $document->append($element);
        $xml = $document->saveXml($element);
        self::assertIsString($xml);

        self::assertStringContainsString('<parameter role="reference">array</parameter>', $xml);
    }

    public function test_to_method_param_xml_variadic(): void
    {
        $param = new ParameterMetaData('args', 1, new SingleType('mixed'), isVariadic: true);

        $document = XMLDocument::createEmpty();
        $element = $param->toMethodParamXml($document);
        $document->append($element);
        $xml = $document->saveXml($element);
        self::assertIsString($xml);

        self::assertStringContainsString('rep="repeat"', $xml);
    }

    public function test_to_method_param_xml_with_attribute(): void
    {
        $param = new ParameterMetaData(
            'key',
            1,
            new SingleType('string'),
            attributes: [new AttributeMetaData('\\SensitiveParameter')],
        );

        $document = XMLDocument::createEmpty();
        $element = $param->toMethodParamXml($document);
        $document->append($element);
        $xml = $document->saveXml($element);
        self::assertIsString($xml);

        self::assertStringContainsString('<modifier role="attribute">', $xml);
        self::assertStringContainsString('\SensitiveParameter', $xml);
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
            self::assertInstanceOf(Element::class, $variableEntry);
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
