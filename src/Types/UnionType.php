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

    private static function sortDnfTypes(SingleType|IntersectionType $a, SingleType|IntersectionType $b): int
    {
        if ($a::class === $b::class) {
            return match ($a::class) {
                SingleType::class => strcmp($a->name, $b->name),
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
                fn (SingleType $l, SingleType $r) => strcmp($l->name, $r->name),
                $a->types,
                $b->types
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
