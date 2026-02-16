<?php

namespace Girgias\StubToDocbook\MetaData\Functions;

use Dom\Element;
use Dom\Text;
use Dom\XMLDocument;
use Girgias\StubToDocbook\FP\Equatable;
use Girgias\StubToDocbook\FP\Utils;
use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\Visibility;
use Girgias\StubToDocbook\Types\DocumentedTypeParser;
use Girgias\StubToDocbook\Types\ReflectionTypeParser;
use Girgias\StubToDocbook\Types\Type;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;

final readonly class FunctionMetaData implements Equatable
{
    /**
     * @param list<ParameterMetaData> $parameters
     * @param list<AttributeMetaData> $attributes
     */
    public function __construct(
        readonly string $name,
        readonly array $parameters,
        readonly Type $returnType,
        readonly string $extension,
        readonly bool $byRefReturn = false,
        readonly array $attributes = [],
        readonly bool $isStatic = false,
        readonly bool $isAbstract = false,
        readonly bool $isFinal = false,
        readonly Visibility $visibility = Visibility::Public,
        readonly bool $isDeprecated = false,
    ) {}

    /**
     * @param FunctionMetaData $other
     */
    public function isSame(mixed $other): bool
    {
        return $this->name === $other->name
            && $this->returnType->isSame($other->returnType)
            && $this->byRefReturn === $other->byRefReturn
            && Utils::equateList($this->parameters, $other->parameters)
            && Utils::equateList($this->attributes, $other->attributes)
            && $this->isStatic === $other->isStatic
            && $this->isAbstract === $other->isAbstract
            && $this->isFinal === $other->isFinal
            && $this->visibility === $other->visibility
            && $this->isDeprecated === $other->isDeprecated
        ;
    }

    public static function fromReflectionData(ReflectionFunction|ReflectionMethod $reflectionData): self
    {
        $isFinal = false;
        $isAbstract = false;
        $visibility = Visibility::Public;

        $reflectionType = $reflectionData->getReturnType();
        if ($reflectionType !== null) {
            $returnType = ReflectionTypeParser::convertFromReflectionType($reflectionData->getReturnType());
        } else {
            /* We need to grab the type from the doc comment */
            $comment = $reflectionData->getDocComment()
                ?? throw new \Error("Cannot determine return type (no declared type nor doc comment)");
            preg_match('/@return (.*)/', $comment, $matches);
            $returnType = ReflectionTypeParser::parseTypeFromDocCommentString(trim($matches[1]));
        }
        $parameters = array_map(
            ParameterMetaData::fromReflectionData(...),
            $reflectionData->getParameters(),
        );
        $attributes = array_map(
            AttributeMetaData::fromReflectionData(...),
            $reflectionData->getAttributes(),
        );

        if ($reflectionData instanceof ReflectionMethod) {
            $isFinal = $reflectionData->isFinal();
            $isAbstract = $reflectionData->isAbstract();
            $visibility = Visibility::fromReflectionData($reflectionData);
        }

        return new self(
            $reflectionData->getName(),
            $parameters,
            $returnType,
            extension: $reflectionData->getExtensionName(),
            byRefReturn: $reflectionData->returnsReference(),
            attributes: $attributes,
            isStatic: $reflectionData->isStatic(),
            isAbstract: $isAbstract,
            isFinal: $isFinal,
            visibility: $visibility,
            isDeprecated: $reflectionData->isDeprecated(),
        );
    }

    /**
     * DocBook 5.2 <methodsynopsis> documentation
     * URL: https://tdg.docbook.org/tdg/5.2/methodsynopsis
     */
    public static function parseFromDoc(Element $element, string $extension): FunctionMetaData
    {

        if ($element->tagName !== 'methodsynopsis') {
            throw new \Exception('Unexpected tag "' . $element->tagName . '"');
        }

        $name = null;
        $returnType = null;
        $byRefReturn = false;
        $isStatic = false;
        $isFinal = false;
        $isAbstract = false;
        $visibility = Visibility::Public;
        $parameters = [];
        $attributes = [];

        foreach ($element->childNodes as $node) {
            if ($node instanceof Text) {
                continue;
            }
            if (($node instanceof Element) === false) {
                throw new \Exception("Unexpected node type: " . $node::class);
            }
            /**
             * methodsynopsis ::=
             *   Sequence of:
             *      info? (db.titleforbidden.info)
             *      Zero or more of:
             *         synopsisinfo
             *      Zero or more of:
             *         modifier
             *         templatename
             *      Optionally one of:
             *         type
             *         void
             *      methodname
             *      Zero or more of:
             *         templatename
             *      One of:
             *         One or more of:
             *             group (db.group.methodparam)
             *             methodparam
             *         void
             *      Zero or more of:
             *         exceptionname
             *         modifier
             *         templatename
             *      Zero or more of:
             *         synopsisinfo
             * @var 'info'|'synopsisinfo'|'modifier'|'templatename'|'type'|'void'|'methodname'|'group'|'methodparam'|'exceptionname' $tagName
             */
            $tagName = $node->tagName;
            match ($tagName) {
                'modifier' => self::parseModifierTag($node, $isStatic, $isFinal, $isAbstract, $visibility, $attributes),
                'type' => $returnType = DocumentedTypeParser::parse($node),
                'void' => $parameters = [],
                'methodname' => $name = self::parseNameWithPossibleClassQualifier($node->textContent),
                'methodparam' => $parameters[] = ParameterMetaData::parseFromMethodParamDocTag($node, count($parameters) + 1),
                'info', 'group', 'exceptionname', 'templatename', 'synopsisinfo'
                    => throw new \Exception('"' . $tagName . '" child tag for <methodsynopsis> is not supported'),
            };
        }

        $deprecatedAttributes = array_filter(
            $attributes,
            fn (AttributeMetaData $attr) => $attr->name === '\Deprecated',
        );
        $isDeprecated = count($deprecatedAttributes) === 1;

        return new FunctionMetaData(
            $name,
            $parameters,
            $returnType,
            extension: $extension,
            byRefReturn: $byRefReturn,
            attributes: $attributes,
            isStatic: $isStatic,
            isAbstract: $isAbstract,
            isFinal: $isFinal,
            visibility: $visibility,
            isDeprecated: $isDeprecated,
        );
    }

    /**
     * DocBook 5.2 <methodsynopsis> generation
     */
    public function toMethodSynopsisXml(XMLDocument $document, ?string $className = null): Element
    {
        $methodsynopsis = $document->createElement('methodsynopsis');

        if ($this->isFinal) {
            $modifier = $document->createElement('modifier');
            $modifier->textContent = 'final';
            $methodsynopsis->append($modifier);
        }
        if ($this->isAbstract) {
            $modifier = $document->createElement('modifier');
            $modifier->textContent = 'abstract';
            $methodsynopsis->append($modifier);
        }
        if ($this->visibility !== Visibility::Public) {
            $modifier = $document->createElement('modifier');
            $modifier->textContent = match ($this->visibility) {
                Visibility::Public => 'public',
                Visibility::Protected => 'protected',
                Visibility::Private => 'private',
            };
            $methodsynopsis->append($modifier);
        }
        if ($this->isStatic) {
            $modifier = $document->createElement('modifier');
            $modifier->textContent = 'static';
            $methodsynopsis->append($modifier);
        }

        foreach ($this->attributes as $attribute) {
            $modifier = $document->createElement('modifier');
            $modifier->setAttribute('role', 'attribute');
            $modifier->textContent = $attribute->name;
            $methodsynopsis->append($modifier);
        }

        $typeFragment = $document->createDocumentFragment();
        $typeFragment->appendXml($this->returnType->toXml());
        $methodsynopsis->append($typeFragment);

        $methodname = $document->createElement('methodname');
        $methodname->textContent = $className !== null
            ? $className . '::' . $this->name
            : $this->name;
        $methodsynopsis->append($methodname);

        if (count($this->parameters) === 0) {
            $methodsynopsis->append($document->createElement('void'));
        } else {
            foreach ($this->parameters as $param) {
                $methodparam = $document->createElement('methodparam');

                if ($param->isOptional) {
                    $methodparam->setAttribute('choice', 'opt');
                }
                if ($param->isVariadic) {
                    $methodparam->setAttribute('rep', 'repeat');
                }

                foreach ($param->attributes as $attr) {
                    $modifier = $document->createElement('modifier');
                    $modifier->setAttribute('role', 'attribute');
                    $modifier->textContent = $attr->name;
                    $methodparam->append($modifier);
                }

                $paramTypeFragment = $document->createDocumentFragment();
                $paramTypeFragment->appendXml($param->type->toXml());
                $methodparam->append($paramTypeFragment);

                $parameter = $document->createElement('parameter');
                $parameter->textContent = $param->name;
                if ($param->isByRef) {
                    $parameter->setAttribute('role', 'reference');
                }
                $methodparam->append($parameter);

                if ($param->defaultValue !== null) {
                    $initializer = $document->createElement('initializer');
                    $initializer->textContent = $param->defaultValue->value;
                    $methodparam->append($initializer);
                }

                $methodsynopsis->append($methodparam);
            }
        }

        return $methodsynopsis;
    }

    private static function parseNameWithPossibleClassQualifier(string $name): string
    {
        if (str_contains($name, '::')) {
            return explode('::', $name)[1];
        }
        return $name;
    }

    /**
     * @param list<AttributeMetaData> $attributes
     */
    private static function parseModifierTag(
        Element $element,
        bool &$isStatic,
        bool &$isFinal,
        bool &$isAbstract,
        Visibility &$visibility,
        array &$attributes,
    ): void {
        match ($element->textContent) {
            'public' => $visibility = Visibility::Public,
            'protected' => $visibility = Visibility::Protected,
            'private' => $visibility = Visibility::Private,
            'static' => $isStatic = true,
            'final' => $isFinal = true,
            'abstract' => $isAbstract = true,
            default => $attributes[] = AttributeMetaData::parseFromDoc($element),
        };
    }
}
