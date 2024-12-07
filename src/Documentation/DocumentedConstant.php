<?php

namespace Girgias\StubToDocbook\Documentation;

final readonly class DocumentedConstant
{
    public function __construct(
        readonly string $name,
        readonly string $type,
        readonly \DOMNode $description,
        readonly string|null $id = null
    ) {}

    public function hasCorrectIdForLinking(): bool
    {
        if ($this->id === null) {
            return false;
        }
        $correctId = 'constant.' . $this::xmlifyName($this->name);
        return $correctId === $this->id;
    }

    public static function xmlifyName(string $label): string
    {
        return str_replace('_', '-', strtolower($label));
    }

    /**
     * DocBook 5.2 <varlistentry> documentation
     * URL: https://tdg.docbook.org/tdg/5.2/varlistentry
     * Returns null if no <constant> tag exist within the <term> tag
     */
    public static function parseFromVarListEntryTag(\DOMElement $entry): ?DocumentedConstant
    {
        $id = null;
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
        if (count($manualTypeTags) === 0) {
            $manualType = 'MISSING';
        } else {
            assert(count($manualTypeTags) === 1);
            $manualType = $manualTypeTags[0]->textContent;
        }

        $manualListItemTags = $entry->getElementsByTagName("listitem");
        /* Guaranteed by the DocBook schema */
        assert(count($manualListItemTags) === 1);
        $manualListItem = $manualListItemTags[0];
        return new DocumentedConstant($manualConstantName, $manualType, $manualListItem, $id);
    }
}