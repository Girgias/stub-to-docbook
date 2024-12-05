<?php

namespace Types;

use Girgias\StubToDocbook\Types\IntersectionType;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;

class IntersectionTypeTest extends TestCase
{
    public function test_generated_xml(): void
    {
        $expected = '<type class="intersection"><type>Countable</type><type>Traversable</type></type>';

        $intersectionType = new IntersectionType([
            new SingleType('Countable'),
            new SingleType('Traversable'),
        ]);

        self::assertSame($expected, $intersectionType->toXml());
    }

    public function test_normalization(): void
    {
        $intersectionType1 = new IntersectionType([
            new SingleType('Countable'),
            new SingleType('Traversable'),
        ]);
        $intersectionType2 = new IntersectionType([
            new SingleType('Traversable'),
            new SingleType('Countable'),
        ]);

        self::assertTrue($intersectionType1->isSame($intersectionType2));
        self::assertTrue($intersectionType2->isSame($intersectionType1));
    }

    public function test_isSame_negative(): void
    {
        $intersectionType1 = new IntersectionType([
            new SingleType('Countable'),
            new SingleType('Traversable'),
        ]);
        $intersectionType2 = new IntersectionType([
            new SingleType('Countable'),
            new SingleType('Exception'),
        ]);
        $intersectionType3 = new IntersectionType([
            new SingleType('Countable'),
            new SingleType('Traversable'),
            new SingleType('Exception'),
        ]);
        $singleType = new SingleType('SingleType');

        self::assertFalse($intersectionType1->isSame($intersectionType2));
        self::assertFalse($intersectionType1->isSame($intersectionType3));
        self::assertFalse($intersectionType1->isSame($singleType));

        self::assertFalse($intersectionType2->isSame($intersectionType1));
        self::assertFalse($intersectionType2->isSame($intersectionType3));
        self::assertFalse($intersectionType2->isSame($singleType));

        self::assertFalse($intersectionType3->isSame($intersectionType1));
        self::assertFalse($intersectionType3->isSame($intersectionType2));
        self::assertFalse($intersectionType3->isSame($singleType));

        self::assertFalse($singleType->isSame($intersectionType1));
        self::assertFalse($singleType->isSame($intersectionType2));
        self::assertFalse($singleType->isSame($intersectionType3));
    }
}
