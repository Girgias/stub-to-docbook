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
        /* We need to define the UNKNOWN constant in the stubs for BetterReflection to be able to
         * parse stubs files, but we don't actually want to deal with it */
        $ignoredConstants[] = ZendEngineReflector::STUB_UNKNOWN_NAME;
        $consts = array_map(
            ConstantMetaData::fromReflectionData(...),
            $reflectionData,
        );
        $constNames = array_map(fn(ConstantMetaData $constant) => $constant->name, $consts);
        $constDict = array_combine($constNames, $consts);
        foreach ($ignoredConstants as $name) {
            unset($constDict[$name]);
        }
        return new self($constDict);
    }

    public function count(): int
    {
        return count($this->constants);
    }
}
