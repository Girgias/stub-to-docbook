<?php

namespace Girgias\StubToDocbook\Types;

final readonly class UnionType implements Type
{
    /** @var list<Type> */
    public readonly array $types;

    /** @param list<Type> $types */
    public function __construct(array $types)
    {
        usort($types, self::sortDnfTypes(...));
        $this->types = $types;
    }

    public function isSame(Type $type): bool
    {
        if ($this::class !== $type::class) {
            return false;
        }
        if (count($this->types) !== count($type->types)) {
            return false;
        }
        /**
         * If the types are the same we will have list<true>,
         * otherwise we have list<true|false>
         * which we can reduce using the && operator
         * to true for the former case, and false for all other cases
         **/
        return array_reduce(
            array_map(
                fn (Type $typeFromThis, Type $typeFromOther) => $typeFromThis->isSame($typeFromOther),
                $this->types,
                $type->types
            ),
            fn (bool $carry, bool $item) => $carry && $item,
            true
        );
    }

    public function toXml(): string
    {
        return implode([
            '<type class="union">',
            ...array_map(fn (Type $type) => $type->toXml(), $this->types),
            '</type>',
        ]);
    }

    private static function sortDnfTypes(Type $a, Type $b): int
    {
        if ($a::class === $b::class) {
            return match ($a::class) {
                SingleType::class => $a->name <=> $b->name,
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
