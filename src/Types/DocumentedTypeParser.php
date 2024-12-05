<?php

namespace Girgias\StubToDocbook\Types;

use DOMElement;

final class DocumentedTypeParser
{
    public static function parse(DOMElement $type): Type
    {
        if ($type->tagName !== 'type') {
            throw new \Exception('Unexpected tag "' . $type->tagName . '"');
        }
        $classAttribute = $type->attributes->getNamedItem('class');
        if ($classAttribute === null) {
            /* Simple type */
            return new SingleType($type->textContent);
        }
        return match ($classAttribute->value) {
            'union' => self::parseTypeList($type, UnionType::class),
            'intersection' => self::parseTypeList($type, IntersectionType::class),
        };
    }

    /**
     * @param DOMElement $type
     * @param class-string $className
     * @return UnionType|IntersectionType
     */
    private static function parseTypeList(DOMElement $type, string $className): UnionType|IntersectionType
    {
        $types = iterator_to_array($type->childNodes->getIterator());
        return new $className(array_map(self::parse(...), $types));
    }
}
