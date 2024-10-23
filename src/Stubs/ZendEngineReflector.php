<?php

namespace Girgias\StubToDocbook\Stubs;

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

final class ZendEngineReflector
{
    /**
     * @param list<SourceLocator> $locators
     */
    public static function newZendEngineReflector(array $locators = []): Reflector
    {
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            new StringSourceLocator('<?php const UNKNOWN = null;', $astLocator),
            ...$locators,
        ]));

        return $reflector;
    }
}