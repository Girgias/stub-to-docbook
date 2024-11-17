<?php

namespace Girgias\StubToDocbook\Stubs;

use Countable;
use Roave\BetterReflection\Reflection\ReflectionConstant;

final readonly class StubConstantList implements Countable
{
    private function __construct(
        /** @param array<string, StubConstant> $constants */
        readonly array $constants
    ) {}

    /**
     * @param list<ReflectionConstant> $reflectionData
     * @param list<string> $ignoredConstants
     * @return self
     */
    public static function fromReflectionDataArray(array $reflectionData, array $ignoredConstants = []): self
    {
        $ignoredConstants[] = 'UNKNOWN';
        $stubConstList = array_filter(
            array_map(
                StubConstant::fromReflectionData(...),
                $reflectionData
            ),
            fn (StubConstant $constant) => !in_array($constant->name, $ignoredConstants)
        );
        $stubConstName = array_map(fn (StubConstant $constant) => $constant->name, $stubConstList);
        return new self(array_combine($stubConstName, $stubConstList));
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