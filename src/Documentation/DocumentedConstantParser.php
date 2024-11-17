<?php

namespace Girgias\StubToDocbook\Documentation;

use DOMElement;

class DocumentedConstantParser
{
    /** @return list<DocumentedConstantList> */
    public static function parse(\DOMDocument $doc): array
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
                assert($entry instanceof DOMElement);

                if ($entry->parentNode !== $variableList) {
                    continue;
                }

                $id = null;
                if ($entry->hasAttribute('xml:id')) {
                    $id = $entry->getAttribute('xml:id');
                }

                $terms = $entry->getElementsByTagName("term");
                assert(count($terms) === 1);

                $manualConstantTags = $terms[0]->getElementsByTagName("constant");
                /* See reference/filter/constants.xml with Available options variable lists */
                if ($manualConstantTags->length === 0) {
                    /* Break out of the inner list dealing with the constants */
                    continue 2;
                }
                assert(count($manualConstantTags) === 1);
                $manualConstantName = $manualConstantTags[0]->textContent;

                $manualTypeTags = $terms[0]->getElementsByTagName("type");
                if (count($manualTypeTags) === 0) {
                    $manualType = 'MISSING';
                } else {
                    assert(count($manualTypeTags) === 1);
                    $manualType = $manualTypeTags[0]->textContent;
                }

                $manualListItemTags = $entry->getElementsByTagName("listitem");
                assert(count($manualListItemTags) === 1);
                $manualListItem = $manualListItemTags[0];

                $individualList[$manualConstantName] = new DocumentedConstant($manualConstantName, $manualType, $manualListItem, $id);
            }

            $constants[] = new DocumentedConstantList(DocumentedConstantListType::VarEntryList, $individualList);
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
                $constants[] = new DocumentedConstantList(DocumentedConstantListType::Table, []);
                continue;
            }
            if (str_contains($theadEntries->item(2)->textContent, 'Notes')) {
                // TODO Handle exoteric docs
                $col = 1;
                foreach ($theadEntries as $theadEntry) {
                    echo 'Column ', $col++, ': ', $theadEntry->textContent, "\n";
                }
                $constants[] = new DocumentedConstantList(DocumentedConstantListType::Table, []);
                continue;
            }

            $tbody = $table->getElementsByTagName("tbody")->item(0);
            $individualList = [];
            foreach ($tbody->getElementsByTagName("row") as $row) {
                assert($row instanceof DOMElement);

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
                    $manualType = 'MISSING';
                } else {
                    assert(count($manualTypeTags) === 1);
                    $manualType = $manualTypeTags[0]->textContent;
                }

                $individualList[$manualConstantName] = new DocumentedConstant($manualConstantName, $manualType, $descriptionEntry, $id);
            }
            $constants[] = new DocumentedConstantList(DocumentedConstantListType::Table, $individualList);
        }

        if ($constants === []) {
            throw new \Exception("No <varlistentry> or <row> tags");
        }
        return $constants;
    }
}