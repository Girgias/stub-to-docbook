<?php

namespace Versions;

use Dom\XMLDocument;
use Girgias\StubToDocbook\Versions\VersionEntry;
use Girgias\StubToDocbook\Versions\VersionsXmlGenerator;
use Girgias\StubToDocbook\Versions\VersionsXmlParser;
use PHPUnit\Framework\TestCase;

class VersionsXmlTest extends TestCase
{
    private const VERSIONS_XML = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<versions>
 <function name="array_map" from="PHP 4 &gt;= 4.0.6, PHP 5, PHP 7, PHP 8"/>
 <function name="array_filter" from="PHP 4 &gt;= 4.0.6, PHP 5, PHP 7, PHP 8"/>
 <function name="json_validate" from="PHP 8.3"/>
</versions>
XML;

    public function test_parse_versions_xml(): void
    {
        $doc = XMLDocument::createFromString(self::VERSIONS_XML);
        $entries = VersionsXmlParser::parse($doc);

        self::assertCount(3, $entries);
        self::assertArrayHasKey('array_map', $entries);
        self::assertSame('PHP 4 >= 4.0.6, PHP 5, PHP 7, PHP 8', $entries['array_map']->from);
        self::assertArrayHasKey('json_validate', $entries);
        self::assertSame('PHP 8.3', $entries['json_validate']->from);
    }

    public function test_generate_versions_xml(): void
    {
        $entries = [
            'foo' => new VersionEntry('foo', 'PHP 8.4'),
            'bar' => new VersionEntry('bar', 'PHP 8.3'),
        ];

        $doc = VersionsXmlGenerator::generate($entries);
        $xml = $doc->saveXml();
        self::assertIsString($xml);

        self::assertStringContainsString('name="bar"', $xml);
        self::assertStringContainsString('name="foo"', $xml);
        self::assertStringContainsString('from="PHP 8.4"', $xml);
        // bar should come before foo (sorted)
        $barPos = strpos($xml, 'name="bar"');
        $fooPos = strpos($xml, 'name="foo"');
        self::assertLessThan($fooPos, $barPos);
    }

    public function test_merge_entries(): void
    {
        $existing = [
            'array_map' => new VersionEntry('array_map', 'PHP 4'),
            'json_validate' => new VersionEntry('json_validate', 'PHP 8.3'),
        ];
        $new = [
            'json_validate' => new VersionEntry('json_validate', 'PHP 8.3'),
            'new_function' => new VersionEntry('new_function', 'PHP 8.4'),
        ];

        $merged = VersionsXmlGenerator::merge($existing, $new);

        self::assertCount(3, $merged);
        // Existing entry should not be overwritten
        self::assertSame('PHP 4', $merged['array_map']->from);
        // New entry should be added
        self::assertArrayHasKey('new_function', $merged);
        self::assertSame('PHP 8.4', $merged['new_function']->from);
    }
}
