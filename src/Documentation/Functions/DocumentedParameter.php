<?php

namespace Girgias\StubToDocbook\Documentation\Functions;

use Girgias\StubToDocbook\Documentation\DocumentedAttribute;
use Girgias\StubToDocbook\Types\DocumentedTypeParser;
use Girgias\StubToDocbook\Types\Type;

final readonly class DocumentedParameter
{
    public function __construct(
        readonly string $name,
        readonly int $position,
        readonly Type $type,
        readonly bool $isOptional = false,
        readonly ?string $defaultValue = null,
        readonly bool $isByRef = false,
        readonly bool $isVariadic = false,
        /** @param array<string, DocumentedAttribute> $attributes */
        readonly array $attributes = [],
    ) {}

    /**
     * DocBook 5.2 <methodparam> documentation
     * URL: https://tdg.docbook.org/tdg/5.2/methodparam
     */
    public static function parseFromDoc(\DOMElement $element, int $position): DocumentedParameter
    {
        if ($element->tagName !== 'methodparam') {
            throw new \Exception('Unexpected tag "' . $element->tagName . '"');
        }

        $name = null;
        $type = null;
        $isOptional = false;
        $defaultValue = null;
        $isByRef = false;
        $isVariadic = false;
        $attributes = [];

        $repAttribute = $element->attributes->getNamedItem('rep');
        if ($repAttribute) {
            $isVariadic = match($repAttribute->value) {
                'repeat' => true,
                'norepeat' => false,
            };
        }

        $choiceAttribute = $element->attributes->getNamedItem('choice');
        if ($choiceAttribute) {
            $isOptional = match($choiceAttribute->value) {
                'opt' => true,
                'req' => false,
            };
        }

        foreach ($element->childNodes as $node) {
            if ($node instanceof \DOMText) {
                continue;
            }
            match ($node->tagName) {
                'type' => $type = DocumentedTypeParser::parse($node),
                'parameter' => [$name, $isByRef] = self::parseParameterTag($node),
                'modifier'  => $attributes[] = DocumentedAttribute::parseFromDoc($node),
                'initializer' => $defaultValue = $node->textContent, // TODO Less than ideal as it can have <constant> or <literal> tags
            };
        }

        return new DocumentedParameter(
            $name,
            $position,
            $type,
            $isOptional,
            $defaultValue,
            $isByRef,
            $isVariadic,
            $attributes,
        );
    }

    private static function parseParameterTag(\DOMElement $element): array
    {
        $byRef = false;
        $role = $element->attributes->getNamedItem('role');
        if ($role) {
            if ($role->value === 'reference') {
                $byRef = true;
            } else {
                throw new \Exception('Unexpected <parameter> role attribute with value "' . $role->value . '"');
            }
        }
        return [$element->textContent, $byRef];
    }
}
