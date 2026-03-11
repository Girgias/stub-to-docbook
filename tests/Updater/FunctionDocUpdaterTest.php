<?php

namespace Updater;

use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;
use Girgias\StubToDocbook\MetaData\Functions\ParameterMetaData;
use Girgias\StubToDocbook\Types\SingleType;
use Girgias\StubToDocbook\Updater\FunctionDocUpdater;
use PHPUnit\Framework\TestCase;

class FunctionDocUpdaterTest extends TestCase
{
    public function test_update_return_type(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<methodsynopsis xmlns="http://docbook.org/ns/docbook">
 <type>string</type><methodname>test_func</methodname>
 <void/>
</methodsynopsis>
XML;

        $doc = XMLDocument::createFromString($xml);
        $synopsis = $doc->firstElementChild;

        $stubFunction = new FunctionMetaData(
            'test_func',
            [],
            new SingleType('int'),
            'test',
        );

        $result = FunctionDocUpdater::updateReturnType($synopsis, $stubFunction);
        self::assertTrue($result);

        $output = $doc->saveXml($synopsis);
        self::assertIsString($output);
        self::assertStringContainsString('<type>int</type>', $output);
    }

    public function test_update_parameter_type(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<methodparam xmlns="http://docbook.org/ns/docbook">
 <type>string</type><parameter>input</parameter>
</methodparam>
XML;

        $doc = XMLDocument::createFromString($xml);
        $param = $doc->firstElementChild;

        $stubParam = new ParameterMetaData(
            'input',
            1,
            new SingleType('int'),
        );

        $result = FunctionDocUpdater::updateParameterType($param, $stubParam);
        self::assertTrue($result);

        $output = $doc->saveXml($param);
        self::assertIsString($output);
        self::assertStringContainsString('<type>int</type>', $output);
    }

    public function test_update_parameter_optional(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<methodparam xmlns="http://docbook.org/ns/docbook">
 <type>int</type><parameter>flags</parameter>
</methodparam>
XML;

        $doc = XMLDocument::createFromString($xml);
        $param = $doc->firstElementChild;

        $stubParam = new ParameterMetaData(
            'flags',
            1,
            new SingleType('int'),
            isOptional: true,
        );

        $result = FunctionDocUpdater::updateParameterOptional($param, $stubParam);
        self::assertTrue($result);
        self::assertSame('opt', $param->getAttribute('choice'));

        // Already optional - no change
        $result2 = FunctionDocUpdater::updateParameterOptional($param, $stubParam);
        self::assertFalse($result2);
    }
}
