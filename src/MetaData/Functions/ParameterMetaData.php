<?php

namespace Girgias\StubToDocbook\MetaData\Functions;

use Dom\Element;
use Dom\NodeList;
use Dom\Text;
use Dom\XPath;
use Girgias\StubToDocbook\FP\Equatable;
use Girgias\StubToDocbook\FP\Utils;
use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\Types\DocumentedTypeParser;
use Girgias\StubToDocbook\Types\SingleType;
use Girgias\StubToDocbook\Types\Type;

final readonly class ParameterMetaData implements Equatable
{
    /** @param list<AttributeMetaData> $attributes */
    public function __construct(
        readonly string $name,
        readonly int $position,
        readonly Type $type,
        readonly bool $isOptional = false,
        readonly ?string $defaultValue = null,
        readonly bool $isByRef = false,
        readonly bool $isVariadic = false,
        readonly array $attributes = [],
    ) {}

    /**
     * @param ParameterMetaData $other
     */
    public function isSame(mixed $other): bool
    {
        return $this->name === $other->name
            && $this->position === $other->position
            && $this->isOptional === $other->isOptional
            && $this->defaultValue === $other->defaultValue
            && $this->isByRef === $other->isByRef
            && $this->isVariadic === $other->isVariadic
            && Utils::equateList($this->attributes, $other->attributes)
            && $this->type->isSame($other->type);
    }

    /**
     * DocBook 5.2 <methodparam> documentation
     * URL: https://tdg.docbook.org/tdg/5.2/methodparam
     */
    public static function parseFromMethodParamDocTag(Element $element, int $position): ParameterMetaData
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
            /** @var 'norepeat'|'repeat' $attributeValue */
            $attributeValue = $repAttribute->value;
            $isVariadic = match ($attributeValue) {
                'repeat' => true,
                'norepeat' => false,
            };
        }

        $choiceAttribute = $element->attributes->getNamedItem('choice');
        if ($choiceAttribute) {
            /** @var 'opt'|'req'|'plain' $attributeValue */
            $attributeValue = $choiceAttribute->value;
            $isOptional = match ($attributeValue) {
                'opt' => true,
                'req' => false,
                'plain' => throw new \Exception('"plain" attribute value for <methodparam> is not supported'),
            };
        }

        foreach ($element->childNodes as $node) {
            if ($node instanceof Text) {
                continue;
            }
            if (($node instanceof Element) === false) {
                throw new \Exception("Unexpected node type: " . $node::class);
            }
            /** @var 'funcparams'|'initializer'|'modifier'|'parameter'|'templatename'|'type' $tagName */
            $tagName = $node->tagName;
            match ($tagName) {
                'type' => $type = DocumentedTypeParser::parse($node),
                'parameter' => [$name, $isByRef] = self::parseParameterTag($node),
                'modifier'  => $attributes[] = AttributeMetaData::parseFromDoc($node),
                'initializer' => $defaultValue = $node->textContent, // TODO Less than ideal as it can have <constant> or <literal> tags
                'funcparams', 'templatename' => throw new \Exception('"' . $tagName . '" child tag for <methodparam> is not supported'),
            };
        }

        return new ParameterMetaData(
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

    /** @return array{0: string, 1: bool}  */
    private static function parseParameterTag(Element $element): array
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

    public static function parseFromVaListEntryDocTag(Element $element, int $position): ParameterMetaData
    {
        if ($element->tagName !== 'varlistentry') {
            throw new \Exception('Unexpected tag "' . $element->tagName . '"');
        }
        $doc = $element->ownerDocument;
        $xpath = new XPath($doc);
        $xpath->registerNamespace('db', 'http://docbook.org/ns/docbook');
        /** @var NodeList<Text> $parameterName */
        $parameterName = $xpath->query('db:term/db:parameter/text()', $element);
        if ($parameterName->length !== 1) {
            if ($parameterName->length === 0) {
                throw new \Exception('Unexpected missing <term><parameter> tag sequence');
            } else {
                throw new \Exception('Unexpected multiple <term><parameter> tag sequences');
            }
        }
        return new self($parameterName[0]->wholeText, $position, new SingleType('UNKNOWN'));
    }
}
