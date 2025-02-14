<?php

namespace Girgias\StubToDocbook\Types;

use Girgias\StubToDocbook\FP\Utils;

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
            '<type class="intersection">',
            ...array_map(fn(SingleType $type) => $type->toXml(), $this->types),
            '</type>',
        ]);
    }
}
