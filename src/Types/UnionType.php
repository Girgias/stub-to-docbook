<?php

namespace Girgias\StubToDocbook\Types;

use Girgias\StubToDocbook\FP\Utils;

final readonly class UnionType implements Type
{
    /** @var list<SingleType|IntersectionType> */
    public readonly array $types;

    /** @param list<SingleType|IntersectionType> $types */
    public function __construct(array $types)
    {
        usort($types, self::sortDnfTypes(...));
        $this->types = $types;
    }

    /**
     * @param Type $other
     */
    public function isSame(mixed $other): bool
    {
        if ($this::class !== $other::class) {
            return false;
        }

        return Utils::equateList($this->types, $other->types);
    }

    public function toXml(): string
    {
        return implode([
            '<type class="union">',
            ...array_map(fn (Type $type) => $type->toXml(), $this->types),
            '</type>',
        ]);
    }

    private static function singleTypeWeight(SingleType $t): int
    {
        return match ($t->name) {
            'callable'   => 3,
            'string'     => 4,
            'array'      => 5,
            'true'       => 6,
            'null'       => 7,
            'object'     => 8,
            'int'        => 9,
            'void'       => 10,
            'never'      => 11,
            'float'      => 12,
            'false'      => 13,
            'bool'       => 14,
            default      => 0,
        };
    }

    /** Sort types according to PHP's zend_type_to_string_resolved() so that Reflection output matches */
    private static function sortSingleTypes(SingleType $a, SingleType $b): int
    {
        $weightA = self::singleTypeWeight($a);
        $weightB = self::singleTypeWeight($b);
        if ($weightA === $weightB && $weightA === 0) {
            return strcasecmp($a->name, $b->name);
        }
        return $weightA <=> $weightB;
    }

    private static function sortDnfTypes(SingleType|IntersectionType $a, SingleType|IntersectionType $b): int
    {
        if ($a::class === $b::class) {
            return match ($a::class) {
                /** @phpstan-ignore argument.type (See https://github.com/phpstan/phpstan/issues/12206) */
                SingleType::class => self::sortSingleTypes($a, $b),
                /** @phpstan-ignore argument.type (See https://github.com/phpstan/phpstan/issues/12206) */
                IntersectionType::class => self::sortIntersectionTypes($a, $b),
            };
        }
        if ($a::class === SingleType::class) {
            return 1;
        } else {
            return -1;
        }
    }

    private static function sortIntersectionTypes(IntersectionType $a, IntersectionType $b): int
    {
        $s = count($a->types) <=> count($b->types);
        if ($s === 0) {
            $cmps = array_map(
                fn (SingleType $l, SingleType $r) => self::sortSingleTypes($l, $r),
                $a->types,
                $b->types,
            );
            foreach ($cmps as $cmp) {
                if ($cmp !== 0) {
                    return $cmp;
                }
            }
            return 0;
        } else {
            return $s;
        }
    }
}
