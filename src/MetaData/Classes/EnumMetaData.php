<?php

namespace Girgias\StubToDocbook\MetaData\Classes;

use Dom\Element;
use Dom\Text;
use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\Description;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;
use Girgias\StubToDocbook\Types\ReflectionTypeParser;
use Girgias\StubToDocbook\Types\Type;
use Roave\BetterReflection\Reflection\ReflectionEnum;

final class EnumMetaData
{
    /**
     * @param list<EnumCaseMetaData> $cases
     * @param list<FunctionMetaData> $methods
     * @param list<string> $implements
     * @param list<AttributeMetaData> $attributes
     */
    public function __construct(
        readonly string $name,
        readonly ?Type $backingType,
        readonly array $cases,
        readonly array $methods,
        readonly string $extension,
        readonly string|null $namespace = null,
        readonly array $implements = [],
        readonly array $attributes = [],
        readonly bool $isDeprecated = false,
        readonly Description|null $description = null,
    ) {}

    public static function fromReflectionData(ReflectionEnum $reflectionData): self
    {
        $backingType = null;
        if ($reflectionData->isBacked()) {
            $backingType = ReflectionTypeParser::convertFromReflectionType($reflectionData->getBackingType());
        }

        $cases = array_values(array_map(
            EnumCaseMetaData::fromReflectionData(...),
            $reflectionData->getCases(),
        ));

        $methods = array_values(array_filter(
            array_map(
                FunctionMetaData::fromReflectionData(...),
                $reflectionData->getImmediateMethods(),
            ),
            fn (FunctionMetaData $m) => !in_array($m->name, ['cases', 'from', 'tryFrom'], true),
        ));

        $implements = $reflectionData->getInterfaceClassNames();

        $attributes = array_map(
            AttributeMetaData::fromReflectionData(...),
            $reflectionData->getAttributes(),
        );

        return new self(
            $reflectionData->getShortName(),
            $backingType,
            $cases,
            $methods,
            extension: $reflectionData->getExtensionName(),
            namespace: $reflectionData->getNamespaceName(),
            implements: array_values($implements),
            attributes: $attributes,
            isDeprecated: $reflectionData->isDeprecated(),
            description: Description::fromReflectionData($reflectionData),
        );
    }

    /**
     * DocBook 5.2 <enumsynopsis> documentation
     * URL: https://tdg.docbook.org/tdg/5.2/enumsynopsis
     */
    public static function parseFromDoc(Element $element, string $extension, string|null $namespace): self
    {
        if ($element->tagName !== 'enumsynopsis') {
            throw new \Exception('Unexpected tag "' . $element->tagName . '"');
        }

        $name = null;
        /* TODO: Determine XML markup for backed enums */
        $backingType = null;
        $cases = [];
        $attributes = [];

        foreach ($element->childNodes as $node) {
            if ($node instanceof Text) {
                continue;
            }
            if (($node instanceof Element) === false) {
                throw new \Exception("Unexpected node type: " . $node::class);
            }
            /**
             * enumsynopsis ::=
             *   Sequence of:
             *      info? (db.titleforbidden.info)
             *      Zero or more of:
             *          synopsisinfo
             *      Zero or more of:
             *          modifier
             *          package
             *      Optional sequence of:
             *          enumname
             *          Zero or more of:
             *              modifier
             *      One or more of:
             *          enumitem
             *      Zero or more of:
             *          synopsisinfo
             *
             * @var 'info'|'synopsisinfo'|'modifier'|'package'|'enumname'|'enumitem' $tagName
             */
            $tagName = $node->tagName;
            match ($tagName) {
                'modifier' => $attributes[] = AttributeMetaData::parseFromDoc($node),
                'enumitem' => $cases[] = EnumCaseMetaData::parseFromDoc($node),
                'enumname' => $name = $node->textContent,
                'info', 'package', 'synopsisinfo'
                => throw new \Exception('"' . $tagName . '" child tag for <methodsynopsis> is not supported'),
            };
        }

        $deprecatedAttributes = array_filter(
            $attributes,
            fn (AttributeMetaData $attr) => $attr->name === '\Deprecated',
        );
        $isDeprecated = count($deprecatedAttributes) === 1;

        return new self(
            $name,
            $backingType,
            cases: $cases,
            methods: [],
            extension: $extension,
            namespace: $namespace,
            implements: [],
            attributes: $attributes,
            isDeprecated: $isDeprecated,
        );
    }

    /**
     * DocBook 5.2 <enumsynopsis> generation
     */
    public function toEnumSynopsisXml(XMLDocument $document): Element
    {
        $enumsynopsis = $document->createElement('enumsynopsis');

        $enumname = $document->createElement('enumname');
        $enumname->textContent = $this->name;
        $enumsynopsis->append($enumname);

        foreach ($this->cases as $case) {
            $enumsynopsis->append($case->toEnumItemXml($document));
        }

        return $enumsynopsis;
    }

    /**
     * DocBook 5.2 <enumsynopsis> generation or <packagesynopsis> containing <enumsynopsis>
     */
    public function toSynopsisXml(XMLDocument $document): Element
    {
        $enumSynopsis = $this->toEnumSynopsisXml($document);
        if ($this->namespace) {
            $synopsis = $document->createElement('packagesynopsis');
            $package = $document->createElement('package');
            $package->textContent = $this->namespace;
            $synopsis->appendChild($package);
            $synopsis->appendChild($enumSynopsis);
            return $synopsis;
        } else {
            return $enumSynopsis;
        }
    }
}
