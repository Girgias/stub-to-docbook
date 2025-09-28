<?php

namespace Types;

use Girgias\StubToDocbook\Stubs\ZendEngineReflector;
use Girgias\StubToDocbook\Types\IntersectionType;
use Girgias\StubToDocbook\Types\ReflectionTypeParser;
use Girgias\StubToDocbook\Types\SingleType;
use Girgias\StubToDocbook\Types\UnionType;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

class ReflectionTypeParserTest extends TestCase
{

    const /* string */ STUB_FILE_STR = <<<'STUB'
<?php

/** @generate-class-entries */

function simple_type(): string {}

function simple_nullable_type(): ?int {}

function simple_union_type_with_null(): float|null {}

function simple_union_type(): float|int {}

function simple_intersection_type(): Traversable&Countable {}

function dnf_type(): array|(Traversable&Countable) {}

STUB;

    public function test_are_string_types_properly_parsed(): void
    {
        $expectedSimpleType = new SingleType("string");
        self::assertTrue(
            $expectedSimpleType->isSame(
                ReflectionTypeParser::parseTypeFromDocCommentString(
                    'string',
                )
            )
        );

        $expectedNullbaleSimpleType = new UnionType([
            new SingleType("null"),
            new SingleType("string"),
        ]);
        self::assertTrue(
            $expectedNullbaleSimpleType->isSame(
                ReflectionTypeParser::parseTypeFromDocCommentString(
                    '?string',
                )
            )
        );

        $expectedSimpleUnionType = new UnionType([
            new SingleType("resource"),
            new SingleType("false"),
        ]);
        self::assertTrue(
            $expectedSimpleUnionType->isSame(
                ReflectionTypeParser::parseTypeFromDocCommentString(
                    'resource|false',
                )
            )
        );

        $expectedSimpleIntersectionType = new IntersectionType([
            new SingleType("Countable"),
            new SingleType("Traversable"),
        ]);
        self::assertTrue(
            $expectedSimpleIntersectionType->isSame(
                ReflectionTypeParser::parseTypeFromDocCommentString(
                    'Traversable&Countable',
                )
            )
        );

        $expectedDnfType = new UnionType([
            new SingleType("array"),
            new IntersectionType([
                new SingleType("Countable"),
                new SingleType("Traversable"),
            ])
        ]);
        self::assertTrue(
            $expectedDnfType->isSame(
                ReflectionTypeParser::parseTypeFromDocCommentString(
                    '(Traversable&Countable)|array',
                )
            )
        );
    }

    public function test_can_retrieve_constants(): void
    {
        $astLocator = (new BetterReflection())->astLocator();
        $reflector = ZendEngineReflector::newZendEngineReflector([
            new StringSourceLocator(self::STUB_FILE_STR, $astLocator),
        ]);
        $returnTypes = array_map(
            ReflectionTypeParser::convertFromReflectionType(...),
            array_map(
                fn(ReflectionFunction $rf) => $rf->getReturnType(),
                $reflector->reflectAllFunctions(),
            ),
        );

        self::assertCount(6, $returnTypes);

        $expectedSimpleType = new SingleType('string');
        self::assertTrue($expectedSimpleType->isSame($returnTypes[0]));

        $expectedSimpleNullableType = new UnionType([
            new SingleType('int'),
            new SingleType('null'),
        ]);
        self::assertTrue($expectedSimpleNullableType->isSame($returnTypes[1]));

        $expectedSimpleUnionTypeWithNull = new UnionType([
            new SingleType('float'),
            new SingleType('null'),
        ]);
        self::assertTrue($expectedSimpleUnionTypeWithNull->isSame($returnTypes[2]));

        $expectedSimpleUnionType = new UnionType([
            new SingleType('float'),
            new SingleType('int'),
        ]);
        self::assertTrue($expectedSimpleUnionType->isSame($returnTypes[3]));

        $expectedSimpleIntersectionType = new IntersectionType([
            new SingleType('Traversable'),
            new SingleType('Countable'),
        ]);
        self::assertTrue($expectedSimpleIntersectionType->isSame($returnTypes[4]));

        $expectedDnfType = new UnionType([
            new SingleType('array'),
            new IntersectionType([
                new SingleType('Traversable'),
                new SingleType('Countable'),
            ]),
        ]);
        self::assertTrue($expectedDnfType->isSame($returnTypes[5]));
    }
}
