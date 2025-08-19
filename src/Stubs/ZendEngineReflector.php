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
    public const string STUB_UNKNOWN_NAME = 'UNKNOWN';
    /**
     * @param list<SourceLocator> $locators
     */
    public static function newZendEngineReflector(array $locators = []): Reflector
    {
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = new DefaultReflector(new AggregateSourceLocator([
            /* We need to define the UNKNOWN constant in the stubs for BetterReflection to be able to
             * parse stubs files, but we don't actually want to deal with it */
            new StringSourceLocator('<?php const ' . self::STUB_UNKNOWN_NAME . ' = null;', $astLocator),
            ...$locators,
        ]));

        return $reflector;
    }
}
