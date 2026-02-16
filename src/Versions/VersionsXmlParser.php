<?php

namespace Girgias\StubToDocbook\Versions;

use Dom\XMLDocument;

final class VersionsXmlParser
{
    /**
     * Parse a versions.xml file into a map of function name => VersionEntry.
     *
     * @return array<string, VersionEntry>
     */
    public static function parse(XMLDocument $doc): array
    {
        $entries = [];
        $functions = $doc->getElementsByTagName('function');
        foreach ($functions as $function) {
            $name = $function->getAttribute('name');
            $from = $function->getAttribute('from');
            if ($name !== '' && $from !== '') {
                $entries[$name] = new VersionEntry($name, $from);
            }
        }
        return $entries;
    }

    /**
     * Parse a versions.xml file from a file path.
     *
     * @return array<string, VersionEntry>
     */
    public static function parseFile(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Failed to read file: ' . $path);
        }
        $doc = XMLDocument::createFromString($content);
        return self::parse($doc);
    }
}
