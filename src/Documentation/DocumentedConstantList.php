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
        readonly DocumentedConstantListType $type,
        public array $constants,
        readonly ?string $title = null,
    ) {}

    private function generateXmlTermElement(XMLDocument $document, int $indentationLevel, ConstantMetaData $constant): Element
    {
        $constantElement = $document->createElement("constant");
        $constantElement->textContent = $constant->name;

        $typeFragment = $document->createDocumentFragment();
        $typeFragment->appendXML($constant->type->toXml());

        $termElement = $document->createElement("term");
        $termElement->append(
            "\n",
            str_repeat(" ", $indentationLevel + 1),
            $constantElement,
            "\n",
            str_repeat(" ", $indentationLevel + 1),
            "(",
            $typeFragment,
            ")",
            "\n",
            str_repeat(" ", $indentationLevel),
        );

        return $termElement;
    }

    public function generateXmlList(XMLDocument $document, int $indentationLevel): Element
    {
        if ($this->type != DocumentedConstantListType::VarEntryList) {
            throw new \Exception("Only support for VarEntry list atm");
        }

        $indentationListTag = str_repeat(" ", $indentationLevel);
        $indentationEntry = str_repeat(" ", $indentationLevel + 1);
        $indentationEntrySubTagLevel = $indentationLevel + 2;
        $indentationEntrySubTag = str_repeat(" ", $indentationEntrySubTagLevel);
        $xmlVariableList = $document->createElement('variablelist');

        foreach ($this->constants as $constant) {
            $xmlEntry = $document->createElement('varlistentry');
            $xmlEntry->setAttribute('xml:id', 'constant.' . xmlify_labels($constant->name));

            $xmlListItem = $document->createElement('listitem');
            $xmlListItem->append($constant->description);

            $xmlEntry->append(
                "\n",
                $indentationEntrySubTag,
                $this->generateXmlTermElement($document, $indentationEntrySubTagLevel, $constant),
                "\n",
                $indentationEntrySubTag,
                $xmlListItem,
                "\n",
                $indentationEntry,
            );

            $xmlVariableList->append(
                "\n",
                $indentationEntry,
                $xmlEntry,
            );
        }

        $xmlVariableList->append(
            "\n",
            $indentationListTag,
        );

        return $xmlVariableList;
    }

    public function count(): int
    {
        return count($this->constants);
    }
}
