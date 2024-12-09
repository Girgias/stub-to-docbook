<?php

namespace Girgias\StubToDocbook\Types;

use Dom\Element;

final class DocumentedTypeParser
{
    /**
     * DocBook 5.2 <type> documentation
     * URL: https://tdg.docbook.org/tdg/5.2/type
     */
    public static function parse(Element $type): Type
    {
        if ($type->tagName !== 'type') {
            throw new \Exception('Unexpected tag "' . $type->tagName . '"');
        }
        $classAttribute = $type->attributes->getNamedItem('class');
        if ($classAttribute === null) {
            /* Simple type */
            return new SingleType($type->textContent);
        }
        /** @var 'union'|'intersection' $attributeValue */
        $attributeValue = $classAttribute->value;
        return match ($attributeValue) {
            'union' => self::parseTypeList($type, UnionType::class),
            'intersection' => self::parseTypeList($type, IntersectionType::class),
        };
    }

    /**
     * @param Element $type
     * @param class-string $className
     * @return UnionType|IntersectionType
     */
    private static function parseTypeList(Element $type, string $className): UnionType|IntersectionType
    {
        $types = iterator_to_array($type->childNodes->getIterator());
        return new $className(array_map(self::parse(...), $types));
    }
}
