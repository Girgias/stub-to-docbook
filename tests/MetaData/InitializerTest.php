<?php

namespace MetaData;

use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\Initializer;
use Girgias\StubToDocbook\MetaData\InitializerVariant;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPUnit\Framework\TestCase;

class InitializerTest extends TestCase
{
    public function test_initializer_is_same(): void
    {
        $constant = new Initializer(InitializerVariant::Constant, 'SOME_CONST');
        self::assertTrue($constant->isSame($constant));

        $literal = new Initializer(InitializerVariant::Literal, 'SOME_CONST');
        self::assertFalse($constant->isSame($literal));
        self::assertFalse($literal->isSame($constant));
    }

    public function test_constant_doc_parsing(): void
    {
        $xml = '<initializer><constant>SOME_CONST</constant></initializer>';

        $document = XMLDocument::createFromString($xml);
        $initializer = Initializer::parseFromDoc($document->firstElementChild);

        self::assertSame(InitializerVariant::Constant, $initializer->variant);
        self::assertSame('SOME_CONST', $initializer->value);
    }

    public function test_literal_int_doc_parsing(): void
    {
        $xml = '<initializer><literal>1</literal></initializer>';

        $document = XMLDocument::createFromString($xml);
        $initializer = Initializer::parseFromDoc($document->firstElementChild);

        self::assertSame(InitializerVariant::Literal, $initializer->variant);
        self::assertSame('1', $initializer->value);
    }

    public function test_text_int_doc_parsing(): void
    {
        $xml = '<initializer>1</initializer>';

        $document = XMLDocument::createFromString($xml);
        $initializer = Initializer::parseFromDoc($document->firstElementChild);

        self::assertSame(InitializerVariant::Literal, $initializer->variant);
        self::assertSame('1', $initializer->value);
    }

    public function test_text_empty_array_doc_parsing(): void
    {
        $xml = '<initializer>[]</initializer>';

        $document = XMLDocument::createFromString($xml);
        $initializer = Initializer::parseFromDoc($document->firstElementChild);

        self::assertSame(InitializerVariant::Literal, $initializer->variant);
        self::assertSame('[]', $initializer->value);
    }

    public function test_text_string_doc_parsing(): void
    {
        $xml = '<initializer>"\t"</initializer>';

        $document = XMLDocument::createFromString($xml);
        $initializer = Initializer::parseFromDoc($document->firstElementChild);

        self::assertSame(InitializerVariant::Literal, $initializer->variant);
        self::assertSame('"\t"', $initializer->value);
    }

    /** This is taken from htmlspecialchars() */
    public function test_text_bitmask_doc_parsing(): void
    {
        $xml = '<initializer>ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401</initializer>';

        $document = XMLDocument::createFromString($xml);
        $initializer = Initializer::parseFromDoc($document->firstElementChild);

        self::assertSame(InitializerVariant::BitMask, $initializer->variant);
        self::assertSame('ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401', $initializer->value);
    }

    /** This is taken from reference/stomp/stomp/construct.xml */
    public function test_text_function_doc_parsing(): void
    {
        $xml = '<initializer>ini_get("stomp.default_broker_uri")</initializer>';

        $document = XMLDocument::createFromString($xml);
        $initializer = Initializer::parseFromDoc($document->firstElementChild);

        self::assertSame(InitializerVariant::Function, $initializer->variant);
        self::assertSame('ini_get("stomp.default_broker_uri")', $initializer->value);
    }

    public function test_text_bad_constant_doc_parsing(): void
    {
        $xml = '<initializer>PDO::PARAM_STR</initializer>';

        $document = XMLDocument::createFromString($xml);
        $initializer = Initializer::parseFromDoc($document->firstElementChild);

        self::assertSame(InitializerVariant::Text, $initializer->variant);
        self::assertSame('PDO::PARAM_STR', $initializer->value);
    }

    public function test_from_int_scalar_node(): void
    {
        $node = Int_::fromString('25');
        $initializer = Initializer::fromPhpParserExpr($node);
        self::assertSame(InitializerVariant::Literal, $initializer->variant);
        self::assertSame('25', $initializer->value);

        $node = Int_::fromString('0xFF');
        $initializer = Initializer::fromPhpParserExpr($node);
        self::assertSame(InitializerVariant::Literal, $initializer->variant);
        self::assertSame('0xFF', $initializer->value);

        $node = Int_::fromString('0o25');
        $initializer = Initializer::fromPhpParserExpr($node);
        self::assertSame(InitializerVariant::Literal, $initializer->variant);
        self::assertSame('0o25', $initializer->value);

        $node = Int_::fromString('0b1101');
        $initializer = Initializer::fromPhpParserExpr($node);
        self::assertSame(InitializerVariant::Literal, $initializer->variant);
        self::assertSame('0b1101', $initializer->value);
    }

    public function test_from_float_scalar_node(): void
    {
        $node = Float_::fromString('12.5');
        $initializer = Initializer::fromPhpParserExpr($node);
        self::assertSame(InitializerVariant::Literal, $initializer->variant);
        self::assertSame('12.5', $initializer->value);

        $node = Float_::fromString('2.4578e12');
        $initializer = Initializer::fromPhpParserExpr($node);
        self::assertSame(InitializerVariant::Literal, $initializer->variant);
        self::assertSame('2.4578e12', $initializer->value);
    }

    public function test_from_string_scalar_node(): void
    {
        $node = String_::fromString('"This is a simple string"');
        $initializer = Initializer::fromPhpParserExpr($node);
        self::assertSame(InitializerVariant::Literal, $initializer->variant);
        self::assertSame('"This is a simple string"', $initializer->value);

        $node = String_::fromString("'raw str'");
        $initializer = Initializer::fromPhpParserExpr($node);
        self::assertSame(InitializerVariant::Literal, $initializer->variant);
        self::assertSame("'raw str'", $initializer->value);
    }
}
