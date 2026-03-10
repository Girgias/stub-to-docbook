<?php

namespace Girgias\StubToDocbook\Differ;

final class MemberListDiffer
{
    /**
     * Compare two lists of named members (e.g., methods, properties, constants, cases).
     *
     * @param list<string> $stubMembers Member names from stubs
     * @param list<string> $docMembers Member names from documentation
     */
    public static function diff(array $stubMembers, array $docMembers): MemberListDiff
    {
        $missing = array_values(array_diff($stubMembers, $docMembers));
        $extra = array_values(array_diff($docMembers, $stubMembers));
        $matching = array_values(array_intersect($stubMembers, $docMembers));

        return new MemberListDiff($missing, $extra, $matching);
    }
}
