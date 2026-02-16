<?php

namespace Girgias\StubToDocbook\Documentation;

use Dom\XMLDocument;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Lists\ConstantList;
use Girgias\StubToDocbook\Types\DocumentedTypeParser;
use Girgias\StubToDocbook\Types\SingleType;

class DocumentedConstantParser
{
    /** @return list<ConstantList> */
    public static function parse(XMLDocument $doc, string $extension): array
    {
        $constants = [];

        // TODO Get title for each <variablelist> tag? (can there be multiple?)

        $variableLists = $doc->getElementsByTagName('variablelist');
        foreach ($variableLists as $variableList) {
            // See reference/curl/constants_curl_multi_setopt.xml for why we need this
            if (
                $variableList->hasAttributeNS($variableList->namespaceURI, 'role')
                && $variableList->getAttributeNS($variableList->namespaceURI, 'role') == 'function_parameters'
            ) {
                continue;
            }
            $individualList = [];
            foreach ($variableList->getElementsByTagName("varlistentry") as $entry) {
                if ($entry->parentNode !== $variableList) {
                    continue;
                }
                $constant = ConstantMetaData::parseFromVarListEntryTag($entry, $extension);
                /* See reference/filter/constants.xml with Available options variable lists */
                if ($constant === null) {
                    /* Break out of the inner list dealing with the constants */
                    continue 2;
                }

                $individualList[$constant->name] = $constant;
            }

            $constants[] = new ConstantList($individualList, DocumentedConstantListType::VarEntryList);
        }

        $tables = $doc->getElementsByTagName('table');
        foreach ($tables as $table) {
            $thead = $table->getElementsByTagName("thead")->item(0);
            $theadEntries = $thead->getElementsByTagName("row")->item(0)->getElementsByTagName("entry");
            $columnCount = count($theadEntries);

            $columnLayout = self::determineTableColumnLayout($theadEntries);
            if ($columnLayout === null) {
                $constants[] = new ConstantList([], DocumentedConstantListType::Table);
                continue;
            }

            $tbody = $table->getElementsByTagName("tbody")->item(0);
            $individualList = [];
            foreach ($tbody->getElementsByTagName("row") as $row) {
                $id = null;
                if ($row->hasAttribute('xml:id')) {
                    $id = $row->getAttribute('xml:id');
                }
                $entries = $row->getElementsByTagName("entry");
                if (count($entries) !== $columnCount) {
                    continue;
                }

                $constantEntry = $entries[$columnLayout['constant']];
                $manualConstantTags = $constantEntry->getElementsByTagName("constant");
                if ($manualConstantTags->length === 0) {
                    continue;
                }
                $manualConstantName = $manualConstantTags[0]->textContent;

                $manualType = null;
                if ($columnLayout['type'] !== null) {
                    $typeEntry = $entries[$columnLayout['type']];
                    $manualTypeTags = $typeEntry->getElementsByTagName("type");
                    if (count($manualTypeTags) === 1) {
                        $manualType = DocumentedTypeParser::parse($manualTypeTags[0]);
                        assert($manualType instanceof SingleType);
                    }
                }

                $descriptionEntry = $columnLayout['description'] !== null ? $entries[$columnLayout['description']] : null;

                $individualList[$manualConstantName] = new ConstantMetaData(
                    $manualConstantName,
                    $manualType,
                    $extension,
                    $id,
                    description: $descriptionEntry,
                );
            }
            $constants[] = new ConstantList($individualList, DocumentedConstantListType::Table);
        }

        if ($constants === []) {
            throw new \Exception("No <varlistentry> or <row> tags");
        }
        return $constants;
    }

    /**
     * Determine column positions for constant, type, and description
     * based on the table header content.
     *
     * @return array{constant: int, type: int|null, description: int|null}|null
     */
    private static function determineTableColumnLayout(\Dom\HTMLCollection $headerEntries): ?array
    {
        $columnCount = count($headerEntries);
        $headers = [];
        foreach ($headerEntries as $entry) {
            $headers[] = strtolower(trim($entry->textContent));
        }

        // Standard 3-column layout: Constant, Type, Description/Value
        if ($columnCount === 3 && !str_contains($headers[2], 'notes')) {
            return ['constant' => 0, 'type' => 1, 'description' => 2];
        }

        // 2-column layout: Constant, Description (no type column)
        if ($columnCount === 2) {
            return ['constant' => 0, 'type' => null, 'description' => 1];
        }

        // 4-column layout: Constant, Type, Value/Description, Notes
        if ($columnCount === 4) {
            return ['constant' => 0, 'type' => 1, 'description' => 2];
        }

        // 3-column with Notes: Constant, Value, Notes
        if ($columnCount === 3 && str_contains($headers[2], 'notes')) {
            return ['constant' => 0, 'type' => null, 'description' => 1];
        }

        return null;
    }
}
