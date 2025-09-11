<?php

namespace Girgias\StubToDocbook\MetaData;

use Dom\Element;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;

final class Initializer
{
    public function __construct(
        readonly InitializerVariant $variant,
        readonly string $value,
    ) { }

    /**
     * DocBook 5.2 <initializer> documentation
     * URL: https://tdg.docbook.org/tdg/5.2/initializer
     * We only really support a subset of possible markup that actually occurs in the php.net doc
     */
    public static function parseFromDoc(Element $element): self
    {
        /* Most initializer tags use raw text annoyingly */
        if ($element->childElementCount == 0) {
            $content = $element->textContent;
            if (str_contains($content, '(')) {
                return new self(InitializerVariant::Function, $content);
            }
            if (str_contains($content, '|')) {
                return new self(InitializerVariant::BitMask, $content);
            }
            /* Literal empty arrays */
            if ($content === '[]') {
                return new self(InitializerVariant::Literal, $content);
            }
            /* Literal numbers */
            if (is_numeric($content)) {
                return new self(InitializerVariant::Literal, $content);
            }
            /* Literal strings */
            if ($content[0] === '"' && $content[-1] === '"') {
                return new self(InitializerVariant::Literal, $content);
            }
            return new self(InitializerVariant::Text, $content);
        }
        if ($element->childElementCount != 1) {
            throw new \Exception('<initializer> tag has more than 1 child node');
        }
        $child = $element->firstElementChild;
        /** @phpstan-ignore match.unhandled */
        return match ($child->tagName) {
            'constant' => new self(InitializerVariant::Constant, $child->textContent),
            'literal' => new self(InitializerVariant::Literal, $child->textContent),
        };
    }

    public static function fromPhpParserExpr(Expr $expr): self
    {
        /* @phpstan-ignore match.unhandled */
        return match ($expr::class) {
            Int_::class => new self(InitializerVariant::Literal, (string)$expr->value),
            String_::class => new self(InitializerVariant::Literal, $expr->value),
        };
    }
}
