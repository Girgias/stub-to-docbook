<?php

namespace Girgias\StubToDocbook\MetaData;

use Dom\Element;
use Girgias\StubToDocbook\FP\Equatable;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\PrettyPrinter\Standard;

final class Initializer implements Equatable
{
    public function __construct(
        readonly InitializerVariant $variant,
        readonly string $value,
    ) { }

    public function isSame(mixed $other): bool
    {
        return $this == $other;
    }

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
        return match ($expr::class) {
            Int_::class, String_::class, Float_::class, Array_::class, UnaryMinus::class
                => new self(InitializerVariant::Literal, self::phpParserExprToString($expr)),
            ConstFetch::class, ClassConstFetch::class
                => new self(InitializerVariant::Constant, self::phpParserExprToString($expr)),
            BitwiseOr::class => new self(InitializerVariant::BitMask, self::phpParserExprToString($expr)),
            FuncCall::class => new self(InitializerVariant::Function, self::phpParserExprToString($expr)),
        };
    }

    private static function phpParserExprToString(Expr $expr): string
    {
        // TODO: We trim leading \ prefix, should we do this?
        return ltrim(match ($expr::class) {
            // TODO: Pretty print will not keep original code value, do we care?
            UnaryMinus::class
                => '-' . self::phpParserExprToString($expr->expr),
            Int_::class, Float_::class
                => $expr->getAttribute('rawValue'),
            BitwiseOr::class
                => self::phpParserExprToString($expr->left) . '|' . self::phpParserExprToString($expr->right),
            default => (new Standard())->prettyPrintExpr($expr),
        }, '\\');
    }
}
