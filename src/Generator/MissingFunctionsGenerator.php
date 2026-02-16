<?php

namespace Girgias\StubToDocbook\Generator;

use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;

final class MissingFunctionsGenerator
{
    /**
     * Generate DocBook XML for a single missing function.
     */
    public static function generateOne(FunctionMetaData $function): string
    {
        $document = XMLDocument::createEmpty();
        $element = $function->toMethodSynopsisXml($document);
        $document->append($element);
        $document->formatOutput = true;

        return $document->saveXml($element);
    }

    /**
     * Generate DocBook XML for multiple missing functions grouped by extension.
     * @param array<string, FunctionMetaData> $functions
     * @return array<string, list<string>> Extension name => list of XML strings
     */
    public static function generateByExtension(array $functions): array
    {
        $result = [];
        foreach ($functions as $function) {
            $result[$function->extension][] = self::generateOne($function);
        }
        return $result;
    }
}
