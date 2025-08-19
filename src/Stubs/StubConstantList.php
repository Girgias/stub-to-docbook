<?php

namespace Girgias\StubToDocbook\Stubs;

use Countable;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Roave\BetterReflection\Reflection\ReflectionConstant;

final readonly class StubConstantList implements Countable
{
    /** @param array<string, ConstantMetaData> $constants */
    public function __construct(
        readonly array $constants,
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
                ConstantMetaData::fromReflectionData(...),
                $reflectionData,
            ),
            fn(ConstantMetaData $constant) => !in_array($constant->name, $ignoredConstants),
        );
        $stubConstName = array_map(fn(ConstantMetaData $constant) => $constant->name, $stubConstList);
        return new self(array_combine($stubConstName, $stubConstList));
    }

    public function count(): int
    {
        return count($this->constants);
    }
}
