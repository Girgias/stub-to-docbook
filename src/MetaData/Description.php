<?php

namespace Girgias\StubToDocbook\MetaData;

use Dom\Element;
use Dom\XMLDocument;
use Girgias\StubToDocbook\FP\Equatable;
use phpDocumentor\Reflection\DocBlockFactory;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionEnumCase;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionProperty;

final readonly class Description implements Equatable
{
    /** @var array<string, DescriptionVariant> */
    private const array VARIANT_MAPPING = [
        'simpara' => DescriptionVariant::Text,
        'enumitemdescription' => DescriptionVariant::Enum,
    ];

    public function __construct(
        readonly DescriptionVariant $variant,
        readonly ?string $value,
    ) {}

    public function isSame(mixed $other): bool
    {
        return $this == $other;
    }

    public function toDescriptionXml(XMLDocument $document): Element
    {
        $elementName = array_search($this->variant, self::VARIANT_MAPPING, true)
            ?: throw new \Exception('Unsupported description variant: ' . $this->variant->name);

        $element = $document->createElement($elementName);
        $element->textContent = $this->value;
        return $element;
    }

    public static function fromReflectionData(
        ReflectionClass|ReflectionMethod|ReflectionFunction|ReflectionConstant|ReflectionClassConstant|ReflectionEnumCase|ReflectionProperty|ReflectionParameter $reflectionData,
    ): ?self
    {
        if (!method_exists($reflectionData, 'getDocComment')) {
            return null;
        }

        $variant = match (true) {
            $reflectionData instanceof ReflectionEnumCase => DescriptionVariant::Enum,
            default => DescriptionVariant::Text,
        };

        $description = self::parseDocCommentDescription($reflectionData->getDocComment());
        if (!$description) {
            return null;
        }

        return new self(
            $variant,
            $description,
        );
    }

    public static function parseFromDoc(Element $element): self
    {
        $variant = self::VARIANT_MAPPING[$element->nodeName]
            ?? throw new \Exception('Unsupported description element: ' . $element->nodeName);

        return new self($variant, $element->textContent);
    }

    private static function parseDocCommentDescription(string|null $docComment): ?string
    {
        if ($docComment === null) {
            return null;
        }

        $docblock = DocBlockFactory::createInstance()
            ->create($docComment);

        $summary = $docblock->getSummary();
        $description = $docblock->getDescription()->render();

        return trim(implode(PHP_EOL, [$summary, $description])) ?: null;
    }
}
