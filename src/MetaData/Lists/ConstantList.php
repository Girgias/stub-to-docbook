<?php

namespace Girgias\StubToDocbook\MetaData\Lists;

use Countable;
use Dom\Element;
use Dom\XMLDocument;
use Girgias\StubToDocbook\Documentation\DocumentedConstantListType;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Roave\BetterReflection\Reflection\ReflectionConstant;

final class ConstantList implements Countable
{
    /** @param array<string, ConstantMetaData> $constants */
    public function __construct(
        readonly array $constants,
        readonly DocumentedConstantListType $type = DocumentedConstantListType::VarEntryList,
        readonly ?string $title = null,
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

    public function toXml(XMLDocument $document, int $indentationLevel): Element
    {
        if ($this->type != DocumentedConstantListType::VarEntryList) {
            throw new \Exception("Only support for VarEntry list atm");
        }

        $xmlVariableList = $document->createElement('variablelist');

        foreach ($this->constants as $constant) {
            $xmlEntry = $constant->toVarListEntryXml($document, $indentationLevel + 1);
            $xmlVariableList->append($xmlEntry);
        }

        return $xmlVariableList;
    }
}
