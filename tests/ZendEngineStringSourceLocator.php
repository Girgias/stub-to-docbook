<?php

namespace Girgias\StubToDocbook\Tests;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * We never use a string source locator outsides of tests, as such we need to "fake" an internal locator
 * to correctly set an extension name so that the core code doesn't need to always branch on it.
 */
class ZendEngineStringSourceLocator extends StringSourceLocator
{
    /** @param non-empty-string $source */
    public function __construct(private string $source, Locator $astLocator)
    {
        parent::__construct($source, $astLocator);
    }

    protected function createLocatedSource(Identifier $identifier): LocatedSource|null
    {
        return new InternalLocatedSource(
            $this->source,
            $identifier->getName(),
            'internal',
        );
    }
}
