<?php

namespace Types;

use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;

class SingleTypeTest extends TestCase
{

    public function test_generated_xml(): void
    {
        $expected = '<type>Countable</type>';

        $type = new SingleType('Countable');

        self::assertSame($expected, $type->toXml());
    }

    public function test_isSame(): void
    {
        $type1 = new SingleType('Countable');
        $type2 = new SingleType('Countable');
        $type3 = new SingleType('NotCountable');

        self::assertTrue($type1->isSame($type2));
        self::assertTrue($type2->isSame($type1));

        self::assertFalse($type1->isSame($type3));
        self::assertFalse($type2->isSame($type3));
        self::assertFalse($type3->isSame($type1));
        self::assertFalse($type3->isSame($type2));
    }
}
