<?php

namespace Generator;

use Dom\XMLDocument;
use Girgias\StubToDocbook\Generator\MigrationGuideGenerator;
use PHPUnit\Framework\TestCase;

class MigrationGuideGeneratorTest extends TestCase
{
    public function test_generate_new_functions_section(): void
    {
        $doc = XMLDocument::createEmpty();
        $section = MigrationGuideGenerator::generateNewFunctionsSection(
            $doc,
            ['array_any', 'array_all', 'json_validate'],
        );

        $xml = $doc->saveXml($section);
        self::assertIsString($xml);
        self::assertStringContainsString('<title>New Functions</title>', $xml);
        self::assertStringContainsString('<function>array_any</function>', $xml);
        self::assertStringContainsString('<function>array_all</function>', $xml);
        self::assertStringContainsString('<function>json_validate</function>', $xml);
        self::assertStringContainsString('xml:id="migration.new-functions"', $xml);
    }

    public function test_generate_deprecated_functions_section(): void
    {
        $doc = XMLDocument::createEmpty();
        $section = MigrationGuideGenerator::generateDeprecatedFunctionsSection(
            $doc,
            ['utf8_encode', 'utf8_decode'],
        );

        $xml = $doc->saveXml($section);
        self::assertIsString($xml);
        self::assertStringContainsString('<title>Deprecated Functions</title>', $xml);
        self::assertStringContainsString('<function>utf8_encode</function>', $xml);
        self::assertStringContainsString('<function>utf8_decode</function>', $xml);
    }

    public function test_generate_new_constants_section(): void
    {
        $doc = XMLDocument::createEmpty();
        $section = MigrationGuideGenerator::generateNewConstantsSection(
            $doc,
            ['PHP_MAJOR_VERSION', 'PHP_MINOR_VERSION'],
        );

        $xml = $doc->saveXml($section);
        self::assertIsString($xml);
        self::assertStringContainsString('<title>New Constants</title>', $xml);
        self::assertStringContainsString('<constant>PHP_MAJOR_VERSION</constant>', $xml);
        self::assertStringContainsString('<constant>PHP_MINOR_VERSION</constant>', $xml);
    }
}
