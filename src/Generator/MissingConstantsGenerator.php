<?php

namespace Girgias\StubToDocbook\Generator;

use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\Lists\ConstantList;

final class MissingConstantsGenerator
{
    /**
     * Generate DocBook XML string for a list of missing constants.
     */
    public static function generate(ConstantList $missingConstants): string
    {
        if (count($missingConstants) === 0) {
            return '';
        }

        $document = XMLDocument::createEmpty();
        $element = $missingConstants->toXml($document, 0);
        $document->append($element);
        $document->formatOutput = true;

        $xml = $document->saveXml($element);
        assert($xml !== false);
        return $xml;
    }

    /**
     * Generate DocBook XML grouped by extension.
     * @return array<string, string> Extension name => XML string
     */
    public static function generateByExtension(ConstantList $missingConstants): array
    {
        $byExtension = [];
        foreach ($missingConstants->constants as $constant) {
            $byExtension[$constant->extension][$constant->name] = $constant;
        }

        $result = [];
        foreach ($byExtension as $extension => $constants) {
            $list = new ConstantList($constants);
            $result[$extension] = self::generate($list);
        }

        return $result;
    }
}
