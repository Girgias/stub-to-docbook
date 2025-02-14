<?php

namespace Types;

use Girgias\StubToDocbook\Types\IntersectionType;
use Girgias\StubToDocbook\Types\SingleType;
use Girgias\StubToDocbook\Types\UnionType;
use PHPUnit\Framework\TestCase;

class UnionTypeTest extends TestCase
{
    public function test_generated_simple_union_xml(): void
    {
        $expected = '<type class="union"><type>Countable</type><type>array</type></type>';

        $unionType = new UnionType([
            new SingleType('Countable'),
            new SingleType('array'),
        ]);

        self::assertSame($expected, $unionType->toXml());
    }

    public function test_normalization_simple_unions(): void
    {
        $unionType1 = new UnionType([
            new SingleType('Countable'),
            new SingleType('array'),
        ]);
        $unionType2 = new UnionType([
            new SingleType('array'),
            new SingleType('Countable'),
        ]);

        self::assertTrue($unionType1->isSame($unionType2));
        self::assertTrue($unionType2->isSame($unionType1));
    }

    public function test_normalization_dnf(): void
    {
        $unionType1 = new UnionType([
            new IntersectionType([
                new SingleType('X'),
                new SingleType('Y'),
            ]),
            new IntersectionType([
                new SingleType('A'),
                new SingleType('B'),
            ]),
            new SingleType('array'),
        ]);
        $unionType2 = new UnionType([
            new IntersectionType([
                new SingleType('A'),
                new SingleType('B'),
            ]),
            new IntersectionType([
                new SingleType('X'),
                new SingleType('Y'),
            ]),
            new SingleType('array'),
        ]);

        self::assertTrue($unionType1->isSame($unionType2));
        self::assertTrue($unionType2->isSame($unionType1));
    }

    public function test_normalization_dnf_large(): void
    {
        $unionType1 = new UnionType([
            new IntersectionType([
                new SingleType('X'),
                new SingleType('Y'),
                new SingleType('Z'),
            ]),
            new IntersectionType([
                new SingleType('A'),
                new SingleType('B'),
            ]),
            new SingleType('array'),
            new IntersectionType([
                new SingleType('T'),
                new SingleType('S'),
            ]),
        ]);
        $unionType2 = new UnionType([
            new IntersectionType([
                new SingleType('A'),
                new SingleType('B'),
            ]),
            new IntersectionType([
                new SingleType('X'),
                new SingleType('Y'),
                new SingleType('Z'),
            ]),
            new IntersectionType([
                new SingleType('T'),
                new SingleType('S'),
            ]),
            new SingleType('array'),
        ]);

        self::assertTrue($unionType1->isSame($unionType2));
        self::assertTrue($unionType2->isSame($unionType1));
    }

    public function test_generated_dnf_xml(): void
    {
        $expected = '<type class="union"><type class="intersection"><type>A</type><type>B</type></type><type class="intersection"><type>X</type><type>Y</type></type><type>array</type></type>';

        $unionType = new UnionType([
            new IntersectionType([
                new SingleType('X'),
                new SingleType('Y'),
            ]),
            new IntersectionType([
                new SingleType('A'),
                new SingleType('B'),
            ]),
            new SingleType('array'),
        ]);

        self::assertSame($expected, $unionType->toXml());
    }
}
