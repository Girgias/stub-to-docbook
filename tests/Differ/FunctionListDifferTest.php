<?php

namespace Girgias\StubToDocbook\Tests\Differ;

use Girgias\StubToDocbook\Differ\FunctionListDiffer;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;
use Girgias\StubToDocbook\MetaData\Functions\ParameterMetaData;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;

class FunctionListDifferTest extends TestCase
{
    public function test_no_differences(): void
    {
        $fn = new FunctionMetaData('foo', [], new SingleType('void'), 'ext');
        $diff = FunctionListDiffer::diff(['foo' => $fn], ['foo' => $fn]);

        self::assertSame(1, $diff->valid);
        self::assertSame([], $diff->missing);
        self::assertSame([], $diff->mismatched);
    }

    public function test_missing_function(): void
    {
        $fn = new FunctionMetaData('foo', [], new SingleType('void'), 'ext');
        $diff = FunctionListDiffer::diff(['foo' => $fn], []);

        self::assertSame(0, $diff->valid);
        self::assertCount(1, $diff->missing);
        self::assertArrayHasKey('foo', $diff->missing);
        self::assertSame([], $diff->mismatched);
    }

    public function test_mismatched_return_type(): void
    {
        $stubFn = new FunctionMetaData('bar', [], new SingleType('int'), 'ext');
        $docFn = new FunctionMetaData('bar', [], new SingleType('string'), 'ext');
        $diff = FunctionListDiffer::diff(['bar' => $stubFn], ['bar' => $docFn]);

        self::assertSame(0, $diff->valid);
        self::assertSame([], $diff->missing);
        self::assertCount(1, $diff->mismatched);
        self::assertArrayHasKey('bar', $diff->mismatched);
    }

    public function test_mismatched_parameters(): void
    {
        $stubFn = new FunctionMetaData(
            'baz',
            [new ParameterMetaData('a', 1, new SingleType('int'))],
            new SingleType('void'),
            'ext',
        );
        $docFn = new FunctionMetaData(
            'baz',
            [new ParameterMetaData('a', 1, new SingleType('string'))],
            new SingleType('void'),
            'ext',
        );
        $diff = FunctionListDiffer::diff(['baz' => $stubFn], ['baz' => $docFn]);

        self::assertSame(0, $diff->valid);
        self::assertCount(1, $diff->mismatched);
    }

    public function test_mixed_results(): void
    {
        $validFn = new FunctionMetaData('ok', [], new SingleType('void'), 'ext');
        $missingFn = new FunctionMetaData('gone', [], new SingleType('int'), 'ext');
        $stubMismatch = new FunctionMetaData('diff', [], new SingleType('int'), 'ext');
        $docMismatch = new FunctionMetaData('diff', [], new SingleType('string'), 'ext');

        $stubs = ['ok' => $validFn, 'gone' => $missingFn, 'diff' => $stubMismatch];
        $docs = ['ok' => $validFn, 'diff' => $docMismatch];

        $diff = FunctionListDiffer::diff($stubs, $docs);

        self::assertSame(1, $diff->valid);
        self::assertCount(1, $diff->missing);
        self::assertCount(1, $diff->mismatched);
    }
}
