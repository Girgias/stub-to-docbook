<?php

namespace Girgias\StubToDocbook\MetaData;

use Dom\Element;
use Dom\Node;
use Girgias\StubToDocbook\Types\DocumentedTypeParser;
use Girgias\StubToDocbook\Types\ReflectionTypeParser;
use Girgias\StubToDocbook\Types\SingleType;
use Roave\BetterReflection\Reflection\ReflectionConstant;

final class ConstantMetaData
{
    private function __construct(
        readonly string $name,
        readonly SingleType|null $type,
        readonly string $extension,
        readonly string|null $id,
        readonly string|int|float|null $value = null,
        readonly Node|null $description = null,
    ) {}

    public static function fromReflectionData(ReflectionConstant $reflectionData): self
    {
        $name = $reflectionData->getName();
        $name = match ($name) {
            'TRUE', 'FALSE', 'NULL' => strtolower($name),
            default => $name,
        };
        return new self(
            $name,
            ReflectionTypeParser::parseTypeForConstant($reflectionData),
            get_extension_name_from_reflection_date($reflectionData),
            'constant.' . xmlify_labels($name),
            value: $reflectionData->getValue(),
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
}
