<?php

namespace Girgias\StubToDocbook\Documentation;

use Dom\XMLDocument;

final class DocBookLoader
{
    /**
     * Known DocBook entities used in PHP documentation that map to XML equivalents.
     */
    private const ENTITY_REPLACEMENTS = [
        '&true;' => '<constant xmlns="http://docbook.org/ns/docbook">true</constant>',
        '&false;' => '<constant xmlns="http://docbook.org/ns/docbook">false</constant>',
        '&null;' => '<constant xmlns="http://docbook.org/ns/docbook">null</constant>',
    ];

    /**
     * Load a DocBook XML file, handling entities that cannot be expanded.
     *
     * Replaces known entities with their XML equivalents and escapes
     * remaining entity references to prevent parsing errors.
     */
    public static function loadFile(string $path): XMLDocument
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Failed to read file: ' . $path);
        }
        return self::loadString($content);
    }

    /**
     * Load a DocBook XML string, handling entities that cannot be expanded.
     */
    public static function loadString(string $content): XMLDocument
    {
        $content = self::replaceEntities($content);
        return XMLDocument::createFromString($content);
    }

    /**
     * Replace known entities with XML equivalents and escape remaining ampersands.
     */
    public static function replaceEntities(string $content): string
    {
        // Replace known entities with their XML equivalents
        $content = str_replace(
            array_keys(self::ENTITY_REPLACEMENTS),
            array_values(self::ENTITY_REPLACEMENTS),
            $content,
        );

        // Escape remaining entity references (e.g. &reftitle.description;)
        $content = str_replace('&', '&amp;', $content);

        return $content;
    }
}
