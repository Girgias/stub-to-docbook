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
            // echo "Has <table> constants\n";
            // TODO Parse THEAD to determine the structure of the table as those are inconsistent
            $thead = $table->getElementsByTagName("thead")->item(0);
            $theadEntries = $thead->getElementsByTagName("row")->item(0)->getElementsByTagName("entry");
            if (count($theadEntries) !== 3) {
                //$col = 1;
                //foreach ($theadEntries as $theadEntry) {
                //    echo 'Column ', $col++, ': ', $theadEntry->textContent, "\n";
                //}
                // TODO Handle exoteric docs
                $constants[] = new ConstantList([], DocumentedConstantListType::Table);
                continue;
            }
            if (str_contains($theadEntries->item(2)->textContent, 'Notes')) {
                // TODO Handle exoteric docs
                $col = 1;
                foreach ($theadEntries as $theadEntry) {
                    echo 'Column ', $col++, ': ', $theadEntry->textContent, "\n";
                }
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
                assert(count($entries) === 3);
                $constantEntry = $entries[0];
                $typeEntry = $entries[1];
                $descriptionEntry = $entries[2];

                $manualConstantTags = $constantEntry->getElementsByTagName("constant");
                assert(count($manualConstantTags) === 1);
                $manualConstantName = $manualConstantTags[0]->textContent;

                $manualTypeTags = $typeEntry->getElementsByTagName("type");
                if (count($manualTypeTags) === 0) {
                    $manualType = null;
                } else {
                    assert(count($manualTypeTags) === 1);
                    $manualType = DocumentedTypeParser::parse($manualTypeTags[0]);
                    assert($manualType instanceof SingleType);
                }

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
     * Parse token constant list from a DocBook document (e.g. appendices/tokens.xml).
     * Token constants are all of type int.
     *
     * @return ConstantList
     */
    public static function parseTokenList(XMLDocument $doc, string $extension): ConstantList
    {
        $individualList = [];
        $tables = $doc->getElementsByTagName('table');
        foreach ($tables as $table) {
            $tbody = $table->getElementsByTagName("tbody")->item(0);
            if ($tbody === null) {
                continue;
            }
            foreach ($tbody->getElementsByTagName("row") as $row) {
                $entries = $row->getElementsByTagName("entry");
                if (count($entries) < 1) {
                    continue;
                }
                $constantTags = $entries[0]->getElementsByTagName("constant");
                if ($constantTags->length === 0) {
                    continue;
                }
                $constantName = $constantTags[0]->textContent;
                $id = 'constant.' . xmlify_labels($constantName);

                $individualList[$constantName] = new ConstantMetaData(
                    $constantName,
                    new SingleType('int'),
                    $extension,
                    $id,
                );
            }
        }

        return new ConstantList($individualList, DocumentedConstantListType::TokenList);
    }
}
