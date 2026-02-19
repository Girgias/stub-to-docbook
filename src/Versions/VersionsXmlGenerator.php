<?php

namespace Girgias\StubToDocbook\Versions;

use Dom\XMLDocument;

final class VersionsXmlGenerator
{
    /**
     * Generate a versions.xml document from a list of version entries.
     *
     * @param array<string, VersionEntry> $entries
     */
    public static function generate(array $entries): XMLDocument
    {
        $doc = XMLDocument::createEmpty();
        $root = $doc->createElement('versions');
        $doc->append($root);

        // Sort entries by name for consistent output
        ksort($entries);

        foreach ($entries as $entry) {
            $element = $doc->createElement('function');
            $element->setAttribute('name', $entry->name);
            $element->setAttribute('from', $entry->from);
            $root->append("\n ", $element);
        }
        $root->append("\n");

        return $doc;
    }

    /**
     * Merge new entries into existing entries, only adding ones that don't exist.
     *
     * @param array<string, VersionEntry> $existing
     * @param array<string, VersionEntry> $new
     * @return array<string, VersionEntry>
     */
    public static function merge(array $existing, array $new): array
    {
        foreach ($new as $name => $entry) {
            if (!array_key_exists($name, $existing)) {
                $existing[$name] = $entry;
            }
        }
        return $existing;
    }
}
