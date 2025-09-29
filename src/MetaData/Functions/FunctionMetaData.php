<?php

namespace Girgias\StubToDocbook\MetaData\Functions;

use Dom\Element;
use Dom\Text;
use Girgias\StubToDocbook\FP\Equatable;
use Girgias\StubToDocbook\FP\Utils;
use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\Types\DocumentedTypeParser;
use Girgias\StubToDocbook\Types\ReflectionTypeParser;
use Girgias\StubToDocbook\Types\Type;
use Roave\BetterReflection\Reflection\ReflectionFunction;

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
            && $this->isDeprecated === $other->isDeprecated
        ;
    }

    public static function fromReflectionData(ReflectionFunction $reflectionData): self
    {
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
        return new self(
            $reflectionData->getName(),
            $parameters,
            $returnType,
            $reflectionData->getExtensionName(),
            $reflectionData->returnsReference(),
            $attributes,
            $reflectionData->isStatic(),
            $reflectionData->isDeprecated(),
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
        $isDeprecated = false;
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
                'modifier'  => $attributes[] = AttributeMetaData::parseFromDoc($node),
                'type' => $returnType = DocumentedTypeParser::parse($node),
                'void' => $parameters = [],
                'methodname' => $name = $node->textContent,
                'methodparam' => $parameters[] = ParameterMetaData::parseFromMethodParamDocTag($node, count($parameters) + 1),
                'info', 'group', 'exceptionname', 'templatename', 'synopsisinfo' =>
                    throw new \Exception('"' . $tagName . '" child tag for <methodsynopsis> is not supported'),
            };
        }

        $deprecatedAttributes = array_filter(
            $attributes,
            fn(AttributeMetaData $attr) => $attr->name === '\Deprecated',
        );
        $isDeprecated = count($deprecatedAttributes) === 1;

        return new FunctionMetaData(
            $name,
            $parameters,
            $returnType,
            $extension,
            $byRefReturn,
            $attributes,
            $isStatic,
            $isDeprecated,
        );
    }
}
