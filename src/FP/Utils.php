<?php

namespace Girgias\StubToDocbook\FP;

/**
 * Functional Programming utilities
 */
final class Utils
{
    /**
     * @template T of Equatable
     * @param list<T> $l
     * @param list<T> $r
     * @return bool
     */
    public static function equateList(array $l, array $r): bool
    {
        if (count($l) !== count($r)) {
            return false;
        }
        /**
         * If the entries are the same we will have list<true>,
         * otherwise we have list<true|false>
         * which we can reduce using the && operator
         * to true for the former case, and false for all other cases
         **/
        return array_reduce(
            array_map(
                self::isSame(...),
                $l,
                $r,
            ),
            fn (bool $carry, bool $item) => $carry && $item,
            true,
        );
    }

    private static function isSame(Equatable $l, Equatable $r): bool
    {
        return $l->isSame($r);
    }
}
