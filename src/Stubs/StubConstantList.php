<?php

namespace Girgias\StubToDocbook\Stubs;

use Countable;
use Roave\BetterReflection\Reflection\ReflectionConstant;

final readonly class StubConstantList implements Countable
{
    private function __construct(
        readonly array $constants
    ) {}

    /**
     * @param list<ReflectionConstant> $reflectionData
     * @return self
     */
    public static function fromReflectionDataArray(array $reflectionData): self
    {
        return new self(
            array_values(
                array_filter(
                    array_map(
                        StubConstant::fromReflectionData(...),
                        $reflectionData
                    ),
                    fn (StubConstant $constant) => $constant->name != 'UNKNOWN'
                )
            )
        );
    }

    /**
     * @param list<StubConstant> $stubConstants
     * @return self
     */
    public static function fromArrayOfStubConstants(array $stubConstants): self
    {
        return new self($stubConstants);
    }

    public function count(): int
    {
        return count($this->constants);
    }
}