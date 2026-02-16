<?php

namespace Updater;

use Dom\XMLDocument;
use Girgias\StubToDocbook\Updater\ClassDocUpdater;
use PHPUnit\Framework\TestCase;

class ClassDocUpdaterTest extends TestCase
{
    public function test_update_property_type(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<fieldsynopsis xmlns="http://docbook.org/ns/docbook">
 <modifier>public</modifier>
 <type>string</type>
 <varname>name</varname>
</fieldsynopsis>
XML;

        $doc = XMLDocument::createFromString($xml);
        $field = $doc->firstElementChild;

        $result = ClassDocUpdater::updatePropertyType($field, 'int');
        self::assertTrue($result);

        $output = $doc->saveXml($field);
        self::assertIsString($output);
        self::assertStringContainsString('<type>int</type>', $output);
        self::assertStringNotContainsString('<type>string</type>', $output);
    }

    public function test_update_constant_type(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<fieldsynopsis xmlns="http://docbook.org/ns/docbook">
 <modifier>public</modifier>
 <modifier>const</modifier>
 <type>string</type>
 <constant>MyClass::CONST_A</constant>
</fieldsynopsis>
XML;

        $doc = XMLDocument::createFromString($xml);
        $field = $doc->firstElementChild;

        $result = ClassDocUpdater::updateConstantType($field, 'int');
        self::assertTrue($result);

        $output = $doc->saveXml($field);
        self::assertIsString($output);
        self::assertStringContainsString('<type>int</type>', $output);
    }

    public function test_update_visibility(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<fieldsynopsis xmlns="http://docbook.org/ns/docbook">
 <modifier>public</modifier>
 <type>string</type>
 <varname>name</varname>
</fieldsynopsis>
XML;

        $doc = XMLDocument::createFromString($xml);
        $field = $doc->firstElementChild;

        $result = ClassDocUpdater::updateVisibility($field, 'protected');
        self::assertTrue($result);

        $output = $doc->saveXml($field);
        self::assertIsString($output);
        self::assertStringContainsString('<modifier>protected</modifier>', $output);

        // No change when already correct
        $result2 = ClassDocUpdater::updateVisibility($field, 'protected');
        self::assertFalse($result2);
    }
}
