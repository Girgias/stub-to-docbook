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
            if ($entry->deprecated !== null) {
                $element->setAttribute('deprecated', $entry->deprecated);
            }
            if ($entry->removed !== null) {
                $element->setAttribute('removed', $entry->removed);
            }
            $root->append("\n ", $element);
        }
        $root->append("\n");

        return $doc;
    }

    /**
     * Merge new entries into existing entries.
     *
     * - New entries not in $existing are added.
     * - Existing entries are enriched with deprecated/removed attributes from $new.
     * - Entries in $existing but missing from $new get the removed attribute set
     *   (using $removedInVersion) if not already present.
     *
     * @param array<string, VersionEntry> $existing
     * @param array<string, VersionEntry> $new
     * @return array<string, VersionEntry>
     */
    public static function merge(array $existing, array $new, ?string $removedInVersion = null): array
    {
        foreach ($new as $name => $entry) {
            if (!array_key_exists($name, $existing)) {
                $existing[$name] = $entry;
            } else {
                $current = $existing[$name];
                $deprecated = $current->deprecated ?? $entry->deprecated;
                $removed = $current->removed ?? $entry->removed;
                if ($deprecated !== $current->deprecated || $removed !== $current->removed) {
                    $existing[$name] = new VersionEntry(
                        $current->name,
                        $current->from,
                        $deprecated,
                        $removed,
                    );
                }
            }
        }

        if ($removedInVersion !== null) {
            foreach ($existing as $name => $entry) {
                if (!array_key_exists($name, $new) && $entry->removed === null) {
                    $existing[$name] = new VersionEntry(
                        $entry->name,
                        $entry->from,
                        $entry->deprecated,
                        $removedInVersion,
                    );
                }
            }
        }

        return $existing;
    }
}
