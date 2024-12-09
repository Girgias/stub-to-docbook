<?php

namespace Documentation\Functions;

use Dom\XMLDocument;
use Girgias\StubToDocbook\Documentation\DocumentedAttribute;
use Girgias\StubToDocbook\Documentation\Functions\DocumentedFunction;
use Girgias\StubToDocbook\Documentation\Functions\DocumentedParameter;
use Girgias\StubToDocbook\Types\SingleType;
use Girgias\StubToDocbook\Types\UnionType;
use PHPUnit\Framework\TestCase;

class DocumentedFunctionTest extends TestCase
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
        $fn = DocumentedFunction::parseFromDoc($document->firstElementChild);

        $expectedFunction = new DocumentedFunction(
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
        $fn = DocumentedFunction::parseFromDoc($document->firstElementChild);

        $expectedFunction = new DocumentedFunction(
            'test_variadic',
            [
                new DocumentedParameter(
                    'var',
                    1,
                    new SingleType('mixed'),
                ),
                new DocumentedParameter(
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
        $fn = DocumentedFunction::parseFromDoc($document->firstElementChild);

        $expectedFunction = new DocumentedFunction(
            'test_attribute',
            [
                new DocumentedParameter(
                    'param1',
                    1,
                    new SingleType('mixed'),
                ),
            ],
            new SingleType('bool'),
            attributes: [
                new DocumentedAttribute('\\Deprecated'),
            ]
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
        $fn = DocumentedFunction::parseFromDoc($document->firstElementChild);

        $expectedFunction = new DocumentedFunction(
            'test_complete_function',
            [
                new DocumentedParameter(
                    'param_typical',
                    1,
                    new SingleType('string'),
                ),
                new DocumentedParameter(
                    'param_reference',
                    2,
                    new SingleType('array'),
                    isByRef: true,
                ),
                new DocumentedParameter(
                    'param_sensitive',
                    3,
                    new SingleType('string'),
                    attributes: [
                        new DocumentedAttribute('\\SensitiveParameter'),
                    ],
                ),
                new DocumentedParameter(
                    'param_optional',
                    4,
                    new UnionType([
                        new SingleType('int'),
                        new SingleType('null'),
                    ]),
                    isOptional: true,
                    defaultValue: 'SOME_CONST',
                ),
            ],
            new UnionType([
                new SingleType('string'),
                new SingleType('false'),
            ]),
            attributes: [
                new DocumentedAttribute('\\Deprecated'),
            ]
        );

        self::assertTrue($fn->isSame($expectedFunction));
    }
}
