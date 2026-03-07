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
 <function name="each" from="PHP 4, PHP 5, PHP 7" deprecated="PHP 7.2.0" removed="PHP 8"/>
</versions>
XML;

    public function test_parse_versions_xml(): void
    {
        $doc = XMLDocument::createFromString(self::VERSIONS_XML);
        $entries = VersionsXmlParser::parse($doc);

        self::assertCount(4, $entries);
        self::assertArrayHasKey('array_map', $entries);
        self::assertSame('PHP 4 >= 4.0.6, PHP 5, PHP 7, PHP 8', $entries['array_map']->from);
        self::assertNull($entries['array_map']->deprecated);
        self::assertNull($entries['array_map']->removed);
        self::assertArrayHasKey('json_validate', $entries);
        self::assertSame('PHP 8.3', $entries['json_validate']->from);
        self::assertArrayHasKey('each', $entries);
        self::assertSame('PHP 4, PHP 5, PHP 7', $entries['each']->from);
        self::assertSame('PHP 7.2.0', $entries['each']->deprecated);
        self::assertSame('PHP 8', $entries['each']->removed);
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

    public function test_generate_with_deprecated_and_removed(): void
    {
        $entries = [
            'each' => new VersionEntry('each', 'PHP 4, PHP 5, PHP 7', 'PHP 7.2.0', 'PHP 8'),
            'foo' => new VersionEntry('foo', 'PHP 8.4'),
        ];

        $doc = VersionsXmlGenerator::generate($entries);
        $xml = $doc->saveXml();
        self::assertIsString($xml);

        self::assertStringContainsString('deprecated="PHP 7.2.0"', $xml);
        self::assertStringContainsString('removed="PHP 8"', $xml);
        // foo should not have deprecated or removed
        self::assertStringNotContainsString('name="foo" from="PHP 8.4" deprecated', $xml);
    }

    public function test_merge_adds_new_entries(): void
    {
        $existing = [
            'array_map' => new VersionEntry('array_map', 'PHP 4'),
        ];
        $new = [
            'array_map' => new VersionEntry('array_map', 'PHP 4'),
            'new_function' => new VersionEntry('new_function', 'PHP 8.4'),
        ];

        $merged = VersionsXmlGenerator::merge($existing, $new);

        self::assertCount(2, $merged);
        self::assertSame('PHP 4', $merged['array_map']->from);
        self::assertArrayHasKey('new_function', $merged);
        self::assertSame('PHP 8.4', $merged['new_function']->from);
    }

    public function test_merge_enriches_existing_with_deprecated(): void
    {
        $existing = [
            'each' => new VersionEntry('each', 'PHP 4, PHP 5, PHP 7'),
        ];
        $new = [
            'each' => new VersionEntry('each', 'PHP 4, PHP 5, PHP 7', 'PHP 7.2.0'),
        ];

        $merged = VersionsXmlGenerator::merge($existing, $new);

        self::assertSame('PHP 7.2.0', $merged['each']->deprecated);
        self::assertNull($merged['each']->removed);
    }

    public function test_merge_enriches_existing_with_removed(): void
    {
        $existing = [
            'each' => new VersionEntry('each', 'PHP 4, PHP 5, PHP 7', 'PHP 7.2.0'),
        ];
        $new = [
            'each' => new VersionEntry('each', 'PHP 4, PHP 5, PHP 7', 'PHP 7.2.0', 'PHP 8'),
        ];

        $merged = VersionsXmlGenerator::merge($existing, $new);

        self::assertSame('PHP 7.2.0', $merged['each']->deprecated);
        self::assertSame('PHP 8', $merged['each']->removed);
    }

    public function test_merge_does_not_overwrite_existing_attributes(): void
    {
        $existing = [
            'each' => new VersionEntry('each', 'PHP 4, PHP 5, PHP 7', 'PHP 7.2.0', 'PHP 8'),
        ];
        $new = [
            'each' => new VersionEntry('each', 'PHP 4, PHP 5, PHP 7'),
        ];

        $merged = VersionsXmlGenerator::merge($existing, $new);

        self::assertSame('PHP 7.2.0', $merged['each']->deprecated);
        self::assertSame('PHP 8', $merged['each']->removed);
    }

    public function test_merge_marks_missing_entries_as_removed(): void
    {
        $existing = [
            'old_function' => new VersionEntry('old_function', 'PHP 4, PHP 5, PHP 7'),
            'current_function' => new VersionEntry('current_function', 'PHP 8.0'),
        ];
        $new = [
            'current_function' => new VersionEntry('current_function', 'PHP 8.0'),
        ];

        $merged = VersionsXmlGenerator::merge($existing, $new, 'PHP 8.0');

        self::assertSame('PHP 8.0', $merged['old_function']->removed);
        self::assertNull($merged['current_function']->removed);
    }

    public function test_merge_does_not_overwrite_existing_removed(): void
    {
        $existing = [
            'old_function' => new VersionEntry('old_function', 'PHP 4', null, 'PHP 7.0'),
        ];
        $new = [
            'other' => new VersionEntry('other', 'PHP 8.0'),
        ];

        $merged = VersionsXmlGenerator::merge($existing, $new, 'PHP 8.4');

        // Should keep the original removed version, not overwrite with the new one
        self::assertSame('PHP 7.0', $merged['old_function']->removed);
    }
}
