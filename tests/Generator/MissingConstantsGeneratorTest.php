<?php

namespace Girgias\StubToDocbook\Tests\Generator;

use Girgias\StubToDocbook\Generator\MissingConstantsGenerator;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Lists\ConstantList;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;

class MissingConstantsGeneratorTest extends TestCase
{
    public function test_empty_list_returns_empty_string(): void
    {
        $list = new ConstantList([]);
        self::assertSame('', MissingConstantsGenerator::generate($list));
    }

    public function test_generates_variablelist_xml(): void
    {
        $list = new ConstantList([
            'MY_CONST' => new ConstantMetaData(
                'MY_CONST',
                new SingleType('int'),
                'ext_test',
                null,
                value: 42,
            ),
        ]);

        $xml = MissingConstantsGenerator::generate($list);

        self::assertStringContainsString('<variablelist>', $xml);
        self::assertStringContainsString('<constant>MY_CONST</constant>', $xml);
        self::assertStringContainsString('<type>int</type>', $xml);
    }

    public function test_generates_by_extension(): void
    {
        $list = new ConstantList([
            'EXT_A_CONST' => new ConstantMetaData('EXT_A_CONST', new SingleType('int'), 'ext_a', null),
            'EXT_B_CONST' => new ConstantMetaData('EXT_B_CONST', new SingleType('string'), 'ext_b', null),
            'EXT_A_OTHER' => new ConstantMetaData('EXT_A_OTHER', new SingleType('bool'), 'ext_a', null),
        ]);

        $result = MissingConstantsGenerator::generateByExtension($list);

        self::assertCount(2, $result);
        self::assertArrayHasKey('ext_a', $result);
        self::assertArrayHasKey('ext_b', $result);
        self::assertStringContainsString('EXT_A_CONST', $result['ext_a']);
        self::assertStringContainsString('EXT_A_OTHER', $result['ext_a']);
        self::assertStringContainsString('EXT_B_CONST', $result['ext_b']);
    }
}
