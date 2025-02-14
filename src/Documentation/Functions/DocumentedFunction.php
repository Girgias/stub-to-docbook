<?php

namespace Girgias\StubToDocbook\Documentation\Functions;

use Dom\Element;
use Dom\NodeList;
use Dom\XPath;

final class DocumentedFunction
{
    /**
     * @param string $id
     * @param FunctionMetaData $functionMetaData
     * @param list<ParameterMetaData> $documentedParameters
     * @param list<string> $parameterTagsValues
     */
    public function __construct(
        private(set) string $id,
        private(set) FunctionMetaData $functionMetaData,
        private(set) array $documentedParameters,
        private(set) array $parameterTagsValues,
    ) {}

    public function areAllParametersDocumented(): bool
    {
        if (count($this->documentedParameters) !== count($this->functionMetaData->parameters)) {
            return false;
        }
        $areEqual = array_map(
            fn(ParameterMetaData $synopsis, ParameterMetaData $listEntry) => $synopsis->name === $listEntry->name,
            $this->functionMetaData->parameters,
            $this->documentedParameters,
        );

        return array_reduce(
            $areEqual,
            fn(bool $carry, bool $item) => $carry && $item,
            true,
        );
    }

    public function areAllParameterTagsReferencingFunctionParameters(): bool
    {
        $validParamNames = array_map(
            fn(ParameterMetaData $synopsis) => $synopsis->name,
            $this->functionMetaData->parameters,
        );
        return array_reduce(
            $this->parameterTagsValues,
            fn(bool $carry, string $item) => $carry && in_array($item, $validParamNames, true),
            true,
        );
    }

    public static function parseFromDoc(Element $element): ?DocumentedFunction
    {
        $id = $element->id;
        $parameters = [];

        $doc = $element->ownerDocument;
        $xpath = new XPath($doc);
        $xpath->registerNamespace('db', 'http://docbook.org/ns/docbook');
        /** @var NodeList<Element> $methodSynopsis */
        $methodSynopsis = $xpath->query('db:refsect1[@role="description"]//db:methodsynopsis', $element);
        if ($methodSynopsis->length === 0) {
            throw new \Exception('No <methodsynopsis> tag found for function ' . $id);
        }
        if ($methodSynopsis->length > 1) {
            // TODO Handle more than 1 <methodsynopsis> tag
            return null;
        }
        $fn = FunctionMetaData::parseFromDoc($methodSynopsis[0]);

        /* We only want to select the <varlistentry> from the top level <variablelist>.
         * However, dumb XML markup means that we might encounter a para tag */
        /** @var NodeList<Element> $varListEntries */
        $varListEntries = $xpath->query('db:refsect1[@role="parameters"]//db:variablelist/db:varlistentry', $element);
        $paramNum = 1;
        foreach ($varListEntries as $parameter) {
            $parameters[] = ParameterMetaData::parseFromVaListEntryDocTag($parameter, $paramNum);
            ++$paramNum;
        }

        /** @var NodeList<Element> $parameterTags */
        $parameterTags = $xpath->query('db:parameter', $element);
        $parameterTags = array_map(
            fn(Element $paramTag) => $paramTag->textContent,
            iterator_to_array($parameterTags, false),
        );

        return new self($id, $fn, $parameters, $parameterTags);
    }
}
