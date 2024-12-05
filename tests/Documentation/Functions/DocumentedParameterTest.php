<?php

namespace Documentation\Functions;

use Girgias\StubToDocbook\Documentation\Functions\DocumentedParameter;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;

class DocumentedParameterTest extends TestCase
{
    public function test_basic_parameter_parsing(): void
    {
        $xml = '<methodparam><type>string</type><parameter>param_name</parameter></methodparam>';
        $document = new \DOMDocument();
        $document->loadXML($xml);
        $param = DocumentedParameter::parseFromDoc($document->firstChild, 1);

        $expectedType = new SingleType('string');

        self::assertSame('param_name', $param->name);
        self::assertSame(1, $param->position);
        self::assertTrue($expectedType->isSame($param->type));
        self::assertFalse($param->isOptional);
        self::assertNull($param->defaultValue);
        self::assertFalse($param->isByRef);
        self::assertFalse($param->isVariadic);
        self::assertSame([], $param->attributes);
    }

    public function test_by_ref_parameter_parsing(): void
    {
        $xml = '<methodparam><type>string</type><parameter role="reference">param_name</parameter></methodparam>';
        $document = new \DOMDocument();
        $document->loadXML($xml);
        $param = DocumentedParameter::parseFromDoc($document->firstChild, 1);

        $expectedType = new SingleType('string');

        self::assertTrue($param->isByRef);
        self::assertSame('param_name', $param->name);
        self::assertSame(1, $param->position);
        self::assertTrue($expectedType->isSame($param->type));
        self::assertFalse($param->isOptional);
        self::assertNull($param->defaultValue);
        self::assertFalse($param->isVariadic);
        self::assertSame([], $param->attributes);
    }

    public function test_parameter_parsing_has_attribute(): void
    {
        $xml = '<methodparam><modifier role="attribute">#[\SensitiveParameter]</modifier><type>string</type><parameter>param_name</parameter></methodparam>';
        $document = new \DOMDocument();
        $document->loadXML($xml);
        $param = DocumentedParameter::parseFromDoc($document->firstChild, 1);

        $expectedType = new SingleType('string');

        self::assertSame('param_name', $param->name);
        self::assertSame(1, $param->position);
        self::assertTrue($expectedType->isSame($param->type));
        self::assertFalse($param->isOptional);
        self::assertNull($param->defaultValue);
        self::assertFalse($param->isByRef);
        self::assertFalse($param->isVariadic);
        self::assertCount(1, $param->attributes);
        self::assertSame('\SensitiveParameter', $param->attributes[0]->name);
    }

    public function test_variadic_parameter_parsing(): void
    {
        $xml = '<methodparam rep="repeat"><type>string</type><parameter>param_name</parameter></methodparam>';
        $document = new \DOMDocument();
        $document->loadXML($xml);
        $param = DocumentedParameter::parseFromDoc($document->firstChild, 1);

        $expectedType = new SingleType('string');

        self::assertTrue($param->isVariadic);
        self::assertSame('param_name', $param->name);
        self::assertSame(1, $param->position);
        self::assertTrue($expectedType->isSame($param->type));
        self::assertFalse($param->isOptional);
        self::assertNull($param->defaultValue);
        self::assertFalse($param->isByRef);
        self::assertSame([], $param->attributes);
    }

    public function test_option_parameter_parsing_no_initializer(): void
    {
        $xml = '<methodparam choice="opt"><type>string</type><parameter>param_name</parameter></methodparam>';
        $document = new \DOMDocument();
        $document->loadXML($xml);
        $param = DocumentedParameter::parseFromDoc($document->firstChild, 1);

        $expectedType = new SingleType('string');

        self::assertTrue($param->isOptional);
        self::assertNull($param->defaultValue);
        self::assertSame('param_name', $param->name);
        self::assertSame(1, $param->position);
        self::assertTrue($expectedType->isSame($param->type));
        self::assertFalse($param->isByRef);
        self::assertFalse($param->isVariadic);
        self::assertSame([], $param->attributes);
    }

    public function test_option_parameter_parsing_with_initializer(): void
    {
        $xml = '<methodparam choice="opt"><type>string</type><parameter>param_name</parameter><initializer><constant>SOME_CONST</constant></initializer></methodparam>';
        $document = new \DOMDocument();
        $document->loadXML($xml);
        $param = DocumentedParameter::parseFromDoc($document->firstChild, 1);

        $expectedType = new SingleType('string');

        self::assertTrue($param->isOptional);
        // TODO Parsing of initializer tag is less than ideal.
        self::assertSame('SOME_CONST', $param->defaultValue);
        self::assertSame('param_name', $param->name);
        self::assertSame(1, $param->position);
        self::assertTrue($expectedType->isSame($param->type));
        self::assertFalse($param->isByRef);
        self::assertFalse($param->isVariadic);
        self::assertSame([], $param->attributes);
    }
}
