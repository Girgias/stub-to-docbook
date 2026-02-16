<?php

namespace Girgias\StubToDocbook\Tests\Generator;

use Girgias\StubToDocbook\Generator\MissingFunctionsGenerator;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;
use Girgias\StubToDocbook\MetaData\Functions\ParameterMetaData;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;

class MissingFunctionsGeneratorTest extends TestCase
{
    public function test_generate_single_function(): void
    {
        $fn = new FunctionMetaData(
            'my_func',
            [new ParameterMetaData('arg', 1, new SingleType('string'))],
            new SingleType('bool'),
            'ext_test',
        );

        $xml = MissingFunctionsGenerator::generateOne($fn);

        self::assertStringContainsString('<methodsynopsis>', $xml);
        self::assertStringContainsString('<methodname>my_func</methodname>', $xml);
        self::assertStringContainsString('<type>bool</type>', $xml);
        self::assertStringContainsString('<parameter>arg</parameter>', $xml);
    }

    public function test_generate_by_extension(): void
    {
        $fn1 = new FunctionMetaData('a_func', [], new SingleType('void'), 'ext_a');
        $fn2 = new FunctionMetaData('b_func', [], new SingleType('int'), 'ext_b');
        $fn3 = new FunctionMetaData('a_other', [], new SingleType('string'), 'ext_a');

        $result = MissingFunctionsGenerator::generateByExtension([
            'a_func' => $fn1,
            'b_func' => $fn2,
            'a_other' => $fn3,
        ]);

        self::assertCount(2, $result);
        self::assertArrayHasKey('ext_a', $result);
        self::assertArrayHasKey('ext_b', $result);
        self::assertCount(2, $result['ext_a']);
        self::assertCount(1, $result['ext_b']);
    }
}
