<?php

namespace Girgias\StubToDocbook\Generator;

use Dom\XMLDocument;
use Dom\Element;

final class MigrationGuideGenerator
{
    /**
     * Generate a migration guide section for new functions.
     *
     * @param list<string> $newFunctions
     */
    public static function generateNewFunctionsSection(XMLDocument $document, array $newFunctions): Element
    {
        $section = $document->createElementNS('http://docbook.org/ns/docbook', 'sect2');
        $section->setAttribute('xml:id', 'migration.new-functions');

        $title = $document->createElementNS('http://docbook.org/ns/docbook', 'title');
        $title->textContent = 'New Functions';
        $section->append($title);

        $list = $document->createElementNS('http://docbook.org/ns/docbook', 'simplelist');
        foreach ($newFunctions as $func) {
            $member = $document->createElementNS('http://docbook.org/ns/docbook', 'member');
            $funcElement = $document->createElementNS('http://docbook.org/ns/docbook', 'function');
            $funcElement->textContent = $func;
            $member->append($funcElement);
            $list->append("\n  ", $member);
        }
        $list->append("\n ");
        $section->append("\n ", $list, "\n");

        return $section;
    }

    /**
     * Generate a migration guide section for deprecated functions.
     *
     * @param list<string> $deprecatedFunctions
     */
    public static function generateDeprecatedFunctionsSection(XMLDocument $document, array $deprecatedFunctions): Element
    {
        $section = $document->createElementNS('http://docbook.org/ns/docbook', 'sect2');
        $section->setAttribute('xml:id', 'migration.deprecated-functions');

        $title = $document->createElementNS('http://docbook.org/ns/docbook', 'title');
        $title->textContent = 'Deprecated Functions';
        $section->append($title);

        $list = $document->createElementNS('http://docbook.org/ns/docbook', 'simplelist');
        foreach ($deprecatedFunctions as $func) {
            $member = $document->createElementNS('http://docbook.org/ns/docbook', 'member');
            $funcElement = $document->createElementNS('http://docbook.org/ns/docbook', 'function');
            $funcElement->textContent = $func;
            $member->append($funcElement);
            $list->append("\n  ", $member);
        }
        $list->append("\n ");
        $section->append("\n ", $list, "\n");

        return $section;
    }

    /**
     * Generate a migration guide section for new constants.
     *
     * @param list<string> $newConstants
     */
    public static function generateNewConstantsSection(XMLDocument $document, array $newConstants): Element
    {
        $section = $document->createElementNS('http://docbook.org/ns/docbook', 'sect2');
        $section->setAttribute('xml:id', 'migration.new-constants');

        $title = $document->createElementNS('http://docbook.org/ns/docbook', 'title');
        $title->textContent = 'New Constants';
        $section->append($title);

        $list = $document->createElementNS('http://docbook.org/ns/docbook', 'simplelist');
        foreach ($newConstants as $constant) {
            $member = $document->createElementNS('http://docbook.org/ns/docbook', 'member');
            $constElement = $document->createElementNS('http://docbook.org/ns/docbook', 'constant');
            $constElement->textContent = $constant;
            $member->append($constElement);
            $list->append("\n  ", $member);
        }
        $list->append("\n ");
        $section->append("\n ", $list, "\n");

        return $section;
    }
}
