<?php

namespace Documentation\Functions;

use Dom\Element;
use Dom\XMLDocument;
use Girgias\StubToDocbook\Documentation\Functions\DocumentedFunction;
use Girgias\StubToDocbook\MetaData\AttributeMetaData;
use Girgias\StubToDocbook\MetaData\Functions\FunctionMetaData;
use Girgias\StubToDocbook\MetaData\Functions\ParameterMetaData;
use Girgias\StubToDocbook\MetaData\Initializer;
use Girgias\StubToDocbook\MetaData\InitializerVariant;
use Girgias\StubToDocbook\Types\SingleType;
use Girgias\StubToDocbook\Types\UnionType;
use PHPUnit\Framework\TestCase;

class DocumentedFunctionTest extends TestCase
{
    private const GMP_INIT = __DIR__ . '/xml/gmp-init.xml';
    private const ISSET = __DIR__ . '/xml/isset.xml';
    private const PASSWORD_HASH = __DIR__ . '/xml/password-hash.xml';
    private const MULTI_SYNOPSIS = __DIR__ . '/xml/multi-synopsis.xml';
    public function loadXml(string $file): Element
    {
        $str = file_get_contents($file);
        self::assertIsString($str);
        $str = str_replace('&', '&amp;', $str);
        $xml = XMLDocument::createFromString($str);
        $rootElement = $xml->firstElementChild;
        self::assertInstanceOf(Element::class, $rootElement);
        return $rootElement;
    }

    public function test_parsing_gmp_xml(): void
    {
        $root = $this->loadXml(self::GMP_INIT);
        $documentedFunction = DocumentedFunction::parseFromDoc($root, 'gmp');

        $expectedFn = new FunctionMetaData(
            'gmp_init',
            [
                new ParameterMetaData(
                    'num',
                    1,
                    new UnionType([
                        new SingleType('int'),
                        new SingleType('string'),
                    ]),
                ),
                new ParameterMetaData(
                    'base',
                    2,
                    new SingleType('int'),
                    isOptional: true,
                    defaultValue: new Initializer(
                        InitializerVariant::Literal,
                        '0',
                    ),
                ),
            ],
            new SingleType('GMP'),
            'gmp',
        );

        $expectedDocumentedParams = [
            new ParameterMetaData('num', 1, new SingleType('UNKNOWN')),
            new ParameterMetaData('base', 2, new SingleType('UNKNOWN')),
        ];

        self::assertTrue($expectedFn->isSame($documentedFunction->functionMetaData));
        self::assertTrue($documentedFunction->areAllParametersDocumented());
        self::assertTrue($expectedDocumentedParams[0]->isSame($documentedFunction->documentedParameters[0]));
        self::assertTrue($expectedDocumentedParams[1]->isSame($documentedFunction->documentedParameters[1]));
        self::assertTrue($documentedFunction->areAllParameterTagsReferencingFunctionParameters());
    }

    public function test_parsing_isset_xml(): void
    {
        $root = $this->loadXml(self::ISSET);
        $documentedFunction = DocumentedFunction::parseFromDoc($root, 'core');

        $expectedFn = new FunctionMetaData(
            'isset',
            [
                new ParameterMetaData(
                    'var',
                    1,
                    new SingleType('mixed'),
                ),
                new ParameterMetaData(
                    'vars',
                    2,
                    new SingleType('mixed'),
                    isVariadic: true,
                ),
            ],
            new SingleType('bool'),
            'core',
        );

        $expectedDocumentedParams = [
            new ParameterMetaData('var', 1, new SingleType('UNKNOWN')),
            new ParameterMetaData('vars', 2, new SingleType('UNKNOWN')),
        ];

        self::assertTrue($expectedFn->isSame($documentedFunction->functionMetaData));
        self::assertTrue($documentedFunction->areAllParametersDocumented());
        self::assertTrue($expectedDocumentedParams[0]->isSame($documentedFunction->documentedParameters[0]));
        self::assertTrue($expectedDocumentedParams[1]->isSame($documentedFunction->documentedParameters[1]));
        self::assertTrue($documentedFunction->areAllParameterTagsReferencingFunctionParameters());
    }

    public function test_parsing_password_hash_xml(): void
    {
        $root = $this->loadXml(self::PASSWORD_HASH);
        $documentedFunction = DocumentedFunction::parseFromDoc($root, 'hash');

        $expectedFn = new FunctionMetaData(
            'password_hash',
            [
                new ParameterMetaData(
                    'password',
                    1,
                    new SingleType('string'),
                    attributes: [
                        new AttributeMetaData('\SensitiveParameter'),
                    ],
                ),
                new ParameterMetaData(
                    'algo',
                    2,
                    new UnionType([
                        new SingleType('string'),
                        new SingleType('int'),
                        new SingleType('null'),
                    ]),
                ),
                new ParameterMetaData(
                    'options',
                    3,
                    new SingleType('array'),
                    isOptional: true,
                    defaultValue: new Initializer(
                        InitializerVariant::Literal,
                        '[]',
                    ),
                ),
            ],
            new SingleType('string'),
            'hash',
        );

        $expectedDocumentedParams = [
            new ParameterMetaData('password', 1, new SingleType('UNKNOWN')),
            new ParameterMetaData('algo', 2, new SingleType('UNKNOWN')),
            new ParameterMetaData('options', 2, new SingleType('UNKNOWN')),
        ];

        self::assertTrue($expectedFn->isSame($documentedFunction->functionMetaData));
        self::assertTrue($documentedFunction->areAllParametersDocumented());
        self::assertTrue($expectedDocumentedParams[0]->isSame($documentedFunction->documentedParameters[0]));
        self::assertTrue($expectedDocumentedParams[1]->isSame($documentedFunction->documentedParameters[1]));
        self::assertTrue($documentedFunction->areAllParameterTagsReferencingFunctionParameters());
    }

    public function test_parsing_multiple_methodsynopsis(): void
    {
        $root = $this->loadXml(self::MULTI_SYNOPSIS);
        $results = DocumentedFunction::parseAllFromDoc($root, 'test');

        self::assertCount(2, $results);

        // First synopsis: multi_synopsis(string $input): string
        self::assertInstanceOf(SingleType::class, $results[0]->functionMetaData->returnType);
        self::assertSame('string', $results[0]->functionMetaData->returnType->name);
        self::assertCount(1, $results[0]->functionMetaData->parameters);
        self::assertSame('input', $results[0]->functionMetaData->parameters[0]->name);

        // Second synopsis: multi_synopsis(string $input, int $flags): bool
        self::assertInstanceOf(SingleType::class, $results[1]->functionMetaData->returnType);
        self::assertSame('bool', $results[1]->functionMetaData->returnType->name);
        self::assertCount(2, $results[1]->functionMetaData->parameters);
        self::assertSame('input', $results[1]->functionMetaData->parameters[0]->name);
        self::assertSame('flags', $results[1]->functionMetaData->parameters[1]->name);

        // Both share the same documented parameters and id
        self::assertSame($results[0]->id, $results[1]->id);
        self::assertSame($results[0]->documentedParameters, $results[1]->documentedParameters);

        // parseFromDoc still returns just the first one
        $single = DocumentedFunction::parseFromDoc($root, 'test');
        self::assertNotNull($single);
        self::assertInstanceOf(SingleType::class, $single->functionMetaData->returnType);
        self::assertSame('string', $single->functionMetaData->returnType->name);
    }
}
