<?php

namespace Girgias\StubToDocbook\Updater;

use Dom\Element;
use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;

final class ConstantDocUpdater
{
    /**
     * Update a constant's type in a <varlistentry> element.
     *
     * @return bool Whether the element was modified
     */
    public static function updateTypeInVarListEntry(Element $entry, ConstantMetaData $stubConstant): bool
    {
        $terms = $entry->getElementsByTagName('term');
        if ($terms->length === 0) {
            return false;
        }

        $term = $terms[0];
        $existingTypes = $term->getElementsByTagName('type');
        $newType = $stubConstant->type;

        if ($newType === null) {
            return false;
        }

        $doc = $entry->ownerDocument;
        $ns = $entry->namespaceURI ?? '';
        $newTypeElement = $doc->createElementNS($ns, 'type');
        $newTypeElement->textContent = $newType->name;

        if ($existingTypes->length > 0) {
            // Replace existing type
            $oldType = $existingTypes[0];
            $oldType->parentNode->replaceChild($newTypeElement, $oldType);
        } else {
            // Add type before or after the constant tag
            $constantTags = $term->getElementsByTagName('constant');
            if ($constantTags->length > 0) {
                $constantTag = $constantTags[0];
                // Insert type and parentheses after constant
                $constantTag->after("\n    (", $newTypeElement, ")");
            }
        }

        return true;
    }

    /**
     * Update a constant's type in a table <row> element.
     *
     * @return bool Whether the element was modified
     */
    public static function updateTypeInTableRow(Element $row, ConstantMetaData $stubConstant, int $typeColumnIndex = 1): bool
    {
        $entries = $row->getElementsByTagName('entry');
        if ($entries->length <= $typeColumnIndex) {
            return false;
        }

        $newType = $stubConstant->type;
        if ($newType === null) {
            return false;
        }

        $typeEntry = $entries[$typeColumnIndex];
        $existingTypes = $typeEntry->getElementsByTagName('type');

        $doc = $row->ownerDocument;
        $ns = $row->namespaceURI ?? '';
        $newTypeElement = $doc->createElementNS($ns, 'type');
        $newTypeElement->textContent = $newType->name;

        if ($existingTypes->length > 0) {
            $oldType = $existingTypes[0];
            $oldType->parentNode->replaceChild($newTypeElement, $oldType);
        } else {
            // Clear and add type
            $typeEntry->textContent = '';
            $typeEntry->append($newTypeElement);
        }

        return true;
    }
}
