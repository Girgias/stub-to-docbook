<?php

namespace Girgias\StubToDocbook\MetaData;

use Dom\Element;
use Dom\Node;
use Dom\XMLDocument;
use Girgias\StubToDocbook\Types\DocumentedTypeParser;
use Girgias\StubToDocbook\Types\ReflectionTypeParser;
use Girgias\StubToDocbook\Types\SingleType;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionConstant;

final class ConstantMetaData
{
    /* Constructor is public due to table constant handling */
    /**
     * @param list<AttributeMetaData> $attributes
     */
    public function __construct(
        readonly string $name,
        readonly SingleType|null $type,
        readonly string $extension,
        readonly string|null $id,
        readonly string|int|float|null $value = null,
        readonly array $attributes = [],
        readonly bool $isDeprecated = false,
        readonly bool $isFinal = false,
        readonly Visibility $visibility = Visibility::Public,
        readonly Node|null $description = null,
    ) {}

    public static function fromReflectionData(ReflectionConstant|ReflectionClassConstant $reflectionData): self
    {
        $isFinal = false;
        $visibility = Visibility::Public;

        $name = $reflectionData->getName();
        $name = match ($name) {
            'TRUE', 'FALSE', 'NULL' => strtolower($name),
            default => $name,
        };
        $attributes = array_map(
            AttributeMetaData::fromReflectionData(...),
            $reflectionData->getAttributes(),
        );

        if ($reflectionData instanceof ReflectionClassConstant) {
            $visibility = Visibility::fromReflectionData($reflectionData);
            $isFinal = $reflectionData->isFinal();
        }

        return new self(
            $name,
            ReflectionTypeParser::parseTypeForConstant($reflectionData),
            get_extension_name_from_reflection_date($reflectionData),
            'constant.' . xmlify_labels($name),
            value: $reflectionData->getValue(),
            attributes: $attributes,
            isDeprecated: $reflectionData->isDeprecated(),
            isFinal: $isFinal,
            visibility: $visibility,
        );
    }

    /**
     * DocBook 5.2 <varlistentry> documentation
     * URL: https://tdg.docbook.org/tdg/5.2/varlistentry
     * Returns null if no <constant> tag exist within the <term> tag
     */
    public static function parseFromVarListEntryTag(Element $entry, string $extension): ?self
    {
        $id = null;
        $type = null;
        if ($entry->hasAttribute('xml:id')) {
            $id = $entry->getAttribute('xml:id');
        }

        $terms = $entry->getElementsByTagName("term");
        assert(count($terms) === 1);

        $manualConstantTags = $terms[0]->getElementsByTagName("constant");
        /* See reference/filter/constants.xml with Available options variable lists */
        if ($manualConstantTags->length === 0) {
            return null;
        }
        assert(count($manualConstantTags) === 1);
        $manualConstantName = $manualConstantTags[0]->textContent;

        $manualTypeTags = $terms[0]->getElementsByTagName("type");
        if (count($manualTypeTags) === 1) {
            $type = DocumentedTypeParser::parse($manualTypeTags[0]);
            assert($type instanceof SingleType);
        }

        $manualListItemTags = $entry->getElementsByTagName("listitem");
        /* Guaranteed by the DocBook schema */
        assert(count($manualListItemTags) === 1);
        $manualListItem = $manualListItemTags[0];
        return new self($manualConstantName, $type, $extension, $id, description: $manualListItem);
    }

    public function toVarListEntryXml(XMLDocument $document, int $indentationLevel): Element
    {
        $xmlEntry = $document->createElement('varlistentry');

        if ($this->id) {
            $xmlEntry->setAttribute('xml:id', $this->id);
        }

        $xmlListItem = $document->createElement('listitem');
        if ($this->description) {
            $xmlListItem->append($this->description);
        } else {
            $xmlSimpara = $document->createElement('simpara');
            $xmlSimpara->textContent = 'Description';
            $xmlListItem->append($xmlSimpara);
        }
        $indentationEntrySubTagLevel = $indentationLevel + 1;

        $xmlEntry->append(
            $this->generateXmlTermElement($document, $indentationEntrySubTagLevel),
            $xmlListItem,
        );

        return $xmlEntry;
    }

    // TODO: Currently generates indentation with 2 spaces as this is what
    // dom_xml_output_indents() does internally.
    private function generateXmlTermElement(XMLDocument $document, int $indentationLevel): Element
    {
        $constantElement = $document->createElement('constant');
        $constantElement->textContent = $this->name;

        $typeFragment = $document->createDocumentFragment();
        $typeFragment->appendXml($this->type->toXml());

        $termElement = $document->createElement('term');
        $termElement->append(
            "\n",
            str_repeat('  ', $indentationLevel + 1),
            $constantElement,
            "\n",
            str_repeat('  ', $indentationLevel + 1),
            '(',
            $typeFragment,
            ')',
            "\n",
            str_repeat('  ', $indentationLevel),
        );

        return $termElement;
    }
}
