<?php

namespace Girgias\StubToDocbook\Updater;

use Dom\Element;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;
use Girgias\StubToDocbook\MetaData\Functions\ParameterMetaData;

final class FunctionDocUpdater
{
    /**
     * Update the return type in a <methodsynopsis> element.
     *
     * @return bool Whether the element was modified
     */
    public static function updateReturnType(Element $methodSynopsis, FunctionMetaData $stubFunction): bool
    {
        $doc = $methodSynopsis->ownerDocument;
        $ns = $methodSynopsis->namespaceURI ?? '';

        // Find existing type element
        $existingTypes = [];
        foreach ($methodSynopsis->childNodes as $child) {
            if ($child instanceof Element && $child->tagName === 'type') {
                $existingTypes[] = $child;
                break;
            }
        }

        if (empty($existingTypes)) {
            return false;
        }

        $oldType = $existingTypes[0];
        $newTypeElement = $doc->createElementNS($ns, 'type');
        $newTypeElement->textContent = $stubFunction->returnType->toXml();
        // For simple types, just set the name
        if (preg_match('/<type>([^<]+)<\/type>/', $stubFunction->returnType->toXml(), $m)) {
            $newTypeElement->textContent = $m[1];
        }
        $oldType->parentNode->replaceChild($newTypeElement, $oldType);
        return true;
    }

    /**
     * Update a parameter's type in a <methodparam> element.
     *
     * @return bool Whether the element was modified
     */
    public static function updateParameterType(Element $methodParam, ParameterMetaData $stubParam): bool
    {
        $doc = $methodParam->ownerDocument;
        $ns = $methodParam->namespaceURI ?? '';

        $typeTags = $methodParam->getElementsByTagName('type');
        if ($typeTags->length === 0) {
            return false;
        }

        $oldType = $typeTags[0];
        $newTypeElement = $doc->createElementNS($ns, 'type');
        if (preg_match('/<type>([^<]+)<\/type>/', $stubParam->type->toXml(), $m)) {
            $newTypeElement->textContent = $m[1];
        }
        $oldType->parentNode->replaceChild($newTypeElement, $oldType);

        return true;
    }

    /**
     * Update optional status of a <methodparam> element.
     *
     * @return bool Whether the element was modified
     */
    public static function updateParameterOptional(Element $methodParam, ParameterMetaData $stubParam): bool
    {
        $currentChoice = $methodParam->getAttribute('choice');
        $shouldBeOptional = $stubParam->isOptional;

        if ($shouldBeOptional && $currentChoice !== 'opt') {
            $methodParam->setAttribute('choice', 'opt');
            return true;
        }

        if (!$shouldBeOptional && $currentChoice === 'opt') {
            $methodParam->removeAttribute('choice');
            return true;
        }

        return false;
    }
}
