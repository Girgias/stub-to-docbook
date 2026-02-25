<?php

namespace Girgias\StubToDocbook\Tests\MetaData\Classes;

use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\Classes\EnumCaseMetaData;
use Girgias\StubToDocbook\MetaData\InitializerVariant;
use PHPUnit\Framework\TestCase;

class EnumCaseMetaDataTest extends TestCase
{
    public function test_non_backed_enum_case(): void
    {
        $xml = <<<'XML'
<enumitem>
 <enumidentifier>HalfAwayFromZero</enumidentifier>
 <enumitemdescription>
  Round to the nearest integer.
  If the decimal part is <literal>5</literal>,
  round to the integer with the larger absolute value.
 </enumitemdescription>
</enumitem>
XML;
        $document = XMLDocument::createFromString($xml);
        $case = EnumCaseMetaData::parseFromDoc($document->firstElementChild);

        self::assertSame('HalfAwayFromZero', $case->name);
        self::assertNull($case->value);
    }

    /** Documentation usage of <enumvalue> XML tag hasn't been determined yet, as no use case yet. */
    public function test_backed_enum_case(): void
    {
        $xml = <<<'XML'
<enumitem>
 <enumidentifier>HalfAwayFromZero</enumidentifier>
 <enumvalue>test</enumvalue>
 <enumitemdescription>
  Round to the nearest integer.
  If the decimal part is <literal>5</literal>,
  round to the integer with the larger absolute value.
 </enumitemdescription>
</enumitem>
XML;
        $document = XMLDocument::createFromString($xml);
        $case = EnumCaseMetaData::parseFromDoc($document->firstElementChild);

        self::assertSame('HalfAwayFromZero', $case->name);
        self::assertNotNull($case->value);
        self::assertSame(InitializerVariant::Literal, $case->value->variant);
        self::assertSame('test', $case->value->value);
    }
}
