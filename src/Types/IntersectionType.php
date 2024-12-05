<?php

namespace Girgias\StubToDocbook\Types;

final readonly class IntersectionType implements Type
{
    /** @var list<SingleType> */
    public readonly array $types;

    /** @param list<SingleType> $types */
    public function __construct(array $types)
    {
        usort($types, function (SingleType $a, SingleType $b) {
            return $a->name <=> $b->name;
        });
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
                fn (SingleType $typeFromThis, SingleType $typeFromOther) => $typeFromThis->isSame($typeFromOther),
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
            '<type class="intersection">',
            ...array_map(fn (SingleType $type) => $type->toXml(), $this->types),
            '</type>',
        ]);
    }
}
