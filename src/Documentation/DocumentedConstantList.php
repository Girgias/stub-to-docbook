<?php

namespace Girgias\StubToDocbook\Documentation;

use Countable;
use Dom\Element;
use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;

final class DocumentedConstantList implements Countable
{
    /** @param array<string, ConstantMetaData> $constants */
    public function __construct(
        public array $constants,
        readonly DocumentedConstantListType $type = DocumentedConstantListType::VarEntryList,
        readonly ?string $title = null,
    ) {}

    public function generateXmlList(XMLDocument $document, int $indentationLevel): Element
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

    public function count(): int
    {
        return count($this->constants);
    }
}
