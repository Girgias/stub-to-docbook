<?php

namespace Documentation\Functions;

use Dom\XMLDocument;
use Girgias\StubToDocbook\Documentation\DocumentedAttribute;
use Girgias\StubToDocbook\Documentation\Functions\DocumentedParameter;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;

class DocumentedParameterTest extends TestCase
{
    private static function expected_param(mixed ...$entries): DocumentedParameter {
        return new DocumentedParameter('param_name', 1, new SingleType('string'), ...$entries);
    }

    public function test_basic_parameter_parsing(): void
    {
        $xml = '<methodparam><type>string</type><parameter>param_name</parameter></methodparam>';
        $document = XMLDocument::createFromString($xml);
        $param = DocumentedParameter::parseFromDoc($document->firstElementChild, 1);

        self::assertTrue($param->isSame(self::expected_param()));
    }

    public function test_by_ref_parameter_parsing(): void
    {
        $xml = '<methodparam><type>string</type><parameter role="reference">param_name</parameter></methodparam>';
        $document = XMLDocument::createFromString($xml);
        $param = DocumentedParameter::parseFromDoc($document->firstElementChild, 1);

        self::assertTrue($param->isSame(self::expected_param(
            isByRef: true,
        )));
    }

    public function test_parameter_parsing_has_attribute(): void
    {
        $xml = '<methodparam><modifier role="attribute">#[\SensitiveParameter]</modifier><type>string</type><parameter>param_name</parameter></methodparam>';
        $document = XMLDocument::createFromString($xml);
        $param = DocumentedParameter::parseFromDoc($document->firstElementChild, 1);

        self::assertTrue($param->isSame(self::expected_param(
            attributes: [new DocumentedAttribute('\SensitiveParameter')],
        )));
        self::assertCount(1, $param->attributes);
    }

    public function test_variadic_parameter_parsing(): void
    {
        $xml = '<methodparam rep="repeat"><type>string</type><parameter>param_name</parameter></methodparam>';
        $document = XMLDocument::createFromString($xml);
        $param = DocumentedParameter::parseFromDoc($document->firstElementChild, 1);

        self::assertTrue($param->isSame(self::expected_param(
            isVariadic: true,
        )));
    }

    public function test_option_parameter_parsing_no_initializer(): void
    {
        $xml = '<methodparam choice="opt"><type>string</type><parameter>param_name</parameter></methodparam>';
        $document = XMLDocument::createFromString($xml);
        $param = DocumentedParameter::parseFromDoc($document->firstElementChild, 1);

        self::assertTrue($param->isSame(self::expected_param(
            isOptional: true,
            defaultValue: null,
        )));
    }

    public function test_option_parameter_parsing_with_initializer(): void
    {
        $xml = '<methodparam choice="opt"><type>string</type><parameter>param_name</parameter><initializer><constant>SOME_CONST</constant></initializer></methodparam>';
        $document = XMLDocument::createFromString($xml);
        $param = DocumentedParameter::parseFromDoc($document->firstElementChild, 1);

        self::assertTrue($param->isSame(self::expected_param(
            isOptional: true,
            // TODO Parsing of initializer tag is less than ideal.
            defaultValue: 'SOME_CONST',
        )));
    }
}
