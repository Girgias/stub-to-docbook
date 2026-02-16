<?php

namespace Differ;

use Girgias\StubToDocbook\Differ\ClassDiff;
use Girgias\StubToDocbook\Differ\EnumDiff;
use Girgias\StubToDocbook\Differ\MemberListDiff;
use Girgias\StubToDocbook\Differ\MemberListDiffer;
use PHPUnit\Framework\TestCase;

class MemberListDifferTest extends TestCase
{
    public function test_identical_lists(): void
    {
        $diff = MemberListDiffer::diff(['a', 'b', 'c'], ['a', 'b', 'c']);
        self::assertSame([], $diff->missing);
        self::assertSame([], $diff->extra);
        self::assertSame(['a', 'b', 'c'], $diff->matching);
    }

    public function test_missing_members(): void
    {
        $diff = MemberListDiffer::diff(['a', 'b', 'c'], ['a']);
        self::assertSame(['b', 'c'], $diff->missing);
        self::assertSame([], $diff->extra);
        self::assertSame(['a'], $diff->matching);
    }

    public function test_extra_members(): void
    {
        $diff = MemberListDiffer::diff(['a'], ['a', 'b', 'c']);
        self::assertSame([], $diff->missing);
        self::assertSame(['b', 'c'], $diff->extra);
        self::assertSame(['a'], $diff->matching);
    }

    public function test_empty_lists(): void
    {
        $diff = MemberListDiffer::diff([], []);
        self::assertSame([], $diff->missing);
        self::assertSame([], $diff->extra);
        self::assertSame([], $diff->matching);
    }

    public function test_enum_diff_has_differences(): void
    {
        $noDiff = new EnumDiff(
            'TestEnum',
            new MemberListDiff([], [], ['A', 'B']),
            new MemberListDiff([], [], ['method']),
        );
        self::assertFalse($noDiff->hasDifferences());

        $withDiff = new EnumDiff(
            'TestEnum',
            new MemberListDiff(['C'], [], ['A', 'B']),
            new MemberListDiff([], [], ['method']),
        );
        self::assertTrue($withDiff->hasDifferences());

        $backingMismatch = new EnumDiff(
            'TestEnum',
            new MemberListDiff([], [], []),
            new MemberListDiff([], [], []),
            backingTypeMismatch: true,
        );
        self::assertTrue($backingMismatch->hasDifferences());
    }

    public function test_class_diff_has_differences(): void
    {
        $noDiff = new ClassDiff(
            'TestClass',
            new MemberListDiff([], [], []),
            new MemberListDiff([], [], []),
            new MemberListDiff([], [], []),
        );
        self::assertFalse($noDiff->hasDifferences());

        $withDiff = new ClassDiff(
            'TestClass',
            new MemberListDiff(['CONST_A'], [], []),
            new MemberListDiff([], [], []),
            new MemberListDiff([], ['extraMethod'], []),
        );
        self::assertTrue($withDiff->hasDifferences());
    }
}
