<?php

namespace Generator;

use Dom\XMLDocument;
use Girgias\StubToDocbook\Generator\ClassSynopsisGenerator;
use PHPUnit\Framework\TestCase;

class ClassSynopsisGeneratorTest extends TestCase
{
    public function test_generate_class_synopsis(): void
    {
        $doc = XMLDocument::createEmpty();
        $synopsis = ClassSynopsisGenerator::generateClassSynopsis(
            $doc,
            'MyClass',
            extends: 'BaseClass',
            implements: ['Stringable'],
            constants: [
                ['name' => 'VERSION', 'type' => 'string', 'visibility' => 'public'],
            ],
            properties: [
                ['name' => 'name', 'type' => 'string', 'visibility' => 'public'],
            ],
            methodNames: ['getName', 'setName'],
            isFinal: true,
        );

        $xml = $doc->saveXml($synopsis);
        self::assertIsString($xml);
        self::assertStringContainsString('class="class"', $xml);
        self::assertStringContainsString('<modifier>final</modifier>', $xml);
        self::assertStringContainsString('<classname>MyClass</classname>', $xml);
        self::assertStringContainsString('<classname>BaseClass</classname>', $xml);
        self::assertStringContainsString('<interfacename>Stringable</interfacename>', $xml);
        self::assertStringContainsString('<constant>MyClass::VERSION</constant>', $xml);
        self::assertStringContainsString('<varname>name</varname>', $xml);
        self::assertStringContainsString('MyClass::getName', $xml);
        self::assertStringContainsString('MyClass::setName', $xml);
    }

    public function test_generate_enum_synopsis(): void
    {
        $doc = XMLDocument::createEmpty();
        $synopsis = ClassSynopsisGenerator::generateClassSynopsis(
            $doc,
            'Suit',
            synopsisType: 'enum',
            implements: ['UnitEnum'],
        );

        $xml = $doc->saveXml($synopsis);
        self::assertIsString($xml);
        self::assertStringContainsString('class="enum"', $xml);
        self::assertStringContainsString('<enumname>Suit</enumname>', $xml);
        self::assertStringContainsString('<interfacename>UnitEnum</interfacename>', $xml);
    }

    public function test_generate_abstract_class(): void
    {
        $doc = XMLDocument::createEmpty();
        $synopsis = ClassSynopsisGenerator::generateClassSynopsis(
            $doc,
            'AbstractBase',
            isAbstract: true,
            methodNames: ['doSomething'],
        );

        $xml = $doc->saveXml($synopsis);
        self::assertIsString($xml);
        self::assertStringContainsString('<modifier>abstract</modifier>', $xml);
        self::assertStringContainsString('AbstractBase::doSomething', $xml);
    }

    public function test_generate_attribute_class(): void
    {
        $doc = XMLDocument::createEmpty();
        $synopsis = ClassSynopsisGenerator::generateClassSynopsis(
            $doc,
            'MyAttribute',
            isFinal: true,
            constants: [
                ['name' => 'TARGET_CLASS', 'type' => 'int', 'visibility' => 'public'],
            ],
            methodNames: ['__construct'],
        );

        $xml = $doc->saveXml($synopsis);
        self::assertIsString($xml);
        self::assertStringContainsString('<classname>MyAttribute</classname>', $xml);
        self::assertStringContainsString('<constant>MyAttribute::TARGET_CLASS</constant>', $xml);
        self::assertStringContainsString('MyAttribute::__construct', $xml);
    }
}
