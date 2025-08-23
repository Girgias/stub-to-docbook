<?php

namespace MetaData\Functions;

use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;
use Girgias\StubToDocbook\MetaData\Functions\ParameterMetaData;
use Girgias\StubToDocbook\MetaData\Initializer;
use Girgias\StubToDocbook\MetaData\InitializerVariant;
use Girgias\StubToDocbook\Types\SingleType;
use Girgias\StubToDocbook\Types\UnionType;
use PHPUnit\Framework\TestCase;

class FunctionMetaDataTest extends TestCase
{
    public function test_no_param_function_parsing(): void
    {
        $xml = <<<'XML'
<methodsynopsis>
 <type>string</type><methodname>test_function</methodname>
 <void/>
</methodsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $fn = FunctionMetaData::parseFromDoc($document->firstElementChild);

        $expectedFunction = new FunctionMetaData(
            'test_function',
            [],
            new SingleType('string'),
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_variadic_function_parsing(): void
    {
        $xml = <<<'XML'
<methodsynopsis>
 <type>bool</type><methodname>test_variadic</methodname>
 <methodparam><type>mixed</type><parameter>var</parameter></methodparam>
 <methodparam rep="repeat"><type>mixed</type><parameter>vars</parameter></methodparam>
</methodsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $fn = FunctionMetaData::parseFromDoc($document->firstElementChild);

        $expectedFunction = new FunctionMetaData(
            'test_variadic',
            [
                new ParameterMetaData(
                    'var',
                    1,
                    new SingleType('mixed'),
                ),
                new ParameterMetaData(
                    'vars',
                    2,
                    new SingleType('mixed'),
                    isVariadic: true,
                ),
            ],
            new SingleType('bool'),
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_function_parsing_with_attribute(): void
    {
        $xml = <<<'XML'
<methodsynopsis>
 <modifier role="attribute">#[\Deprecated]</modifier>
 <type>bool</type><methodname>test_attribute</methodname>
 <methodparam><type>mixed</type><parameter>param1</parameter></methodparam>
</methodsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $fn = FunctionMetaData::parseFromDoc($document->firstElementChild);

        $expectedFunction = new FunctionMetaData(
            'test_attribute',
            [
                new ParameterMetaData(
                    'param1',
                    1,
                    new SingleType('mixed'),
                ),
            ],
            new SingleType('bool'),
            attributes: [
                new AttributeMetaData('\\Deprecated'),
            ],
            isDeprecated: true,
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }

    public function test_complete_function_parsing(): void
    {
        $xml = <<<'XML'
<methodsynopsis>
 <modifier role="attribute">#[\Deprecated]</modifier>
 <type class="union"><type>string</type><type>false</type></type><methodname>test_complete_function</methodname>
 <methodparam><type>string</type><parameter>param_typical</parameter></methodparam>
 <methodparam><type>array</type><parameter role="reference">param_reference</parameter></methodparam>
 <methodparam><modifier role="attribute">#[\SensitiveParameter]</modifier><type>string</type><parameter>param_sensitive</parameter></methodparam>
 <methodparam choice="opt"><type class="union"><type>int</type><type>null</type></type><parameter>param_optional</parameter><initializer><constant>SOME_CONST</constant></initializer></methodparam>
</methodsynopsis>
XML;
        $document = XMLDocument::createFromString($xml);
        $fn = FunctionMetaData::parseFromDoc($document->firstElementChild);

        $expectedFunction = new FunctionMetaData(
            'test_complete_function',
            [
                new ParameterMetaData(
                    'param_typical',
                    1,
                    new SingleType('string'),
                ),
                new ParameterMetaData(
                    'param_reference',
                    2,
                    new SingleType('array'),
                    isByRef: true,
                ),
                new ParameterMetaData(
                    'param_sensitive',
                    3,
                    new SingleType('string'),
                    attributes: [
                        new AttributeMetaData('\\SensitiveParameter'),
                    ],
                ),
                new ParameterMetaData(
                    'param_optional',
                    4,
                    new UnionType([
                        new SingleType('int'),
                        new SingleType('null'),
                    ]),
                    isOptional: true,
                    defaultValue: new Initializer(
                        InitializerVariant::Constant,
                        'SOME_CONST',
                    ),
                ),
            ],
            new UnionType([
                new SingleType('string'),
                new SingleType('false'),
            ]),
            attributes: [
                new AttributeMetaData('\\Deprecated'),
            ],
            isDeprecated: true,
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }
}
