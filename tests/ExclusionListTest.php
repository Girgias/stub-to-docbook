<?php

namespace Girgias\StubToDocbook\Tests;

use Girgias\StubToDocbook\ExclusionList;
use PHPUnit\Framework\TestCase;

class ExclusionListTest extends TestCase
{
    public function test_empty_exclusion_list_excludes_nothing(): void
    {
        $list = new ExclusionList();

        self::assertFalse($list->isConstantExcluded('FOO'));
        self::assertFalse($list->isFunctionExcluded('bar'));
        self::assertFalse($list->isClassExcluded('Baz'));
    }

    public function test_constant_exclusion(): void
    {
        $list = new ExclusionList(constantNames: ['ZEND_DEBUG', 'SOME_CONST']);

        self::assertTrue($list->isConstantExcluded('ZEND_DEBUG'));
        self::assertTrue($list->isConstantExcluded('SOME_CONST'));
        self::assertFalse($list->isConstantExcluded('OTHER'));
    }

    public function test_function_exclusion(): void
    {
        $list = new ExclusionList(functionNames: ['zend_test_func']);

        self::assertTrue($list->isFunctionExcluded('zend_test_func'));
        self::assertFalse($list->isFunctionExcluded('array_map'));
    }

    public function test_class_exclusion(): void
    {
        $list = new ExclusionList(classNames: ['ZendTestClass']);

        self::assertTrue($list->isClassExcluded('ZendTestClass'));
        self::assertFalse($list->isClassExcluded('stdClass'));
    }

    public function test_stub_file_exclusion(): void
    {
        $list = new ExclusionList(stubFiles: ['ext/zend_test/test.stub.php']);

        self::assertTrue($list->isStubFileExcluded('/path/to/php-src/ext/zend_test/test.stub.php'));
        self::assertFalse($list->isStubFileExcluded('/path/to/php-src/ext/standard/basic.stub.php'));
    }

    public function test_pattern_exclusion(): void
    {
        $list = new ExclusionList(namePatterns: ['/^MYSQLI_SERVER_/']);

        self::assertTrue($list->isConstantExcluded('MYSQLI_SERVER_QUERY_NO_INDEX_USED'));
        self::assertTrue($list->isFunctionExcluded('MYSQLI_SERVER_SOMETHING'));
        self::assertFalse($list->isConstantExcluded('MYSQLI_CLIENT_FLAG'));
    }

    public function test_filter_map(): void
    {
        $list = new ExclusionList(constantNames: ['BAD']);
        $symbols = ['GOOD' => 1, 'BAD' => 2, 'OK' => 3];

        $filtered = ExclusionList::filterMap($symbols, $list->isConstantExcluded(...));

        self::assertArrayHasKey('GOOD', $filtered);
        self::assertArrayNotHasKey('BAD', $filtered);
        self::assertArrayHasKey('OK', $filtered);
    }
}
