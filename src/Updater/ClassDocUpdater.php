<?php

namespace Girgias\StubToDocbook\Updater;

use Dom\Element;

final class ClassDocUpdater
{
    /**
     * Update a property's type in a <fieldsynopsis> element.
     *
     * @return bool Whether the element was modified
     */
    public static function updatePropertyType(Element $fieldSynopsis, string $newTypeName): bool
    {
        $doc = $fieldSynopsis->ownerDocument;
        $ns = $fieldSynopsis->namespaceURI ?? '';

        $typeTags = $fieldSynopsis->getElementsByTagName('type');
        $newTypeElement = $doc->createElementNS($ns, 'type');
        $newTypeElement->textContent = $newTypeName;

        if ($typeTags->length > 0) {
            $oldType = $typeTags[0];
            $oldType->parentNode->replaceChild($newTypeElement, $oldType);
            return true;
        }

        // Add type before varname
        $varnameTags = $fieldSynopsis->getElementsByTagName('varname');
        if ($varnameTags->length > 0) {
            $varnameTags[0]->before($newTypeElement);
            return true;
        }

        return false;
    }

    /**
     * Update a class constant's type in a <fieldsynopsis> element.
     *
     * @return bool Whether the element was modified
     */
    public static function updateConstantType(Element $fieldSynopsis, string $newTypeName): bool
    {
        $doc = $fieldSynopsis->ownerDocument;
        $ns = $fieldSynopsis->namespaceURI ?? '';

        $typeTags = $fieldSynopsis->getElementsByTagName('type');
        $newTypeElement = $doc->createElementNS($ns, 'type');
        $newTypeElement->textContent = $newTypeName;

        if ($typeTags->length > 0) {
            $oldType = $typeTags[0];
            $oldType->parentNode->replaceChild($newTypeElement, $oldType);
            return true;
        }

        return false;
    }

    /**
     * Update visibility modifier in a <fieldsynopsis> or <methodsynopsis> element.
     *
     * @return bool Whether the element was modified
     */
    public static function updateVisibility(Element $element, string $newVisibility): bool
    {
        $modifiers = $element->getElementsByTagName('modifier');
        foreach ($modifiers as $modifier) {
            $text = $modifier->textContent;
            if (in_array($text, ['public', 'protected', 'private'], true)) {
                if ($text !== $newVisibility) {
                    $modifier->textContent = $newVisibility;
                    return true;
                }
                return false;
            }
        }

        return false;
    }
}
