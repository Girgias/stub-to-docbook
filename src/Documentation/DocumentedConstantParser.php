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

                // TODO This should check for proper linking, but we need XInclude 1.1
                //if (
                //    !$entry->hasAttribute('xml:id')
                //    || !str_starts_with($entry->getAttribute('xml:id'), 'constant.')
                //) {
                //    exit($entry->textContent . PHP_EOL);
                //}

                $terms = $entry->getElementsByTagName("term");
                assert(count($terms) === 1);

                $manualConstantTags = $terms[0]->getElementsByTagName("constant");
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

                $individualList[] = new DocumentedConstant($manualConstantName, $manualType, $manualListItem);
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

                $individualList[] = new DocumentedConstant($manualConstantName, $manualType, $descriptionEntry);
            }
            $constants[] = new DocumentedConstantList(DocumentedConstantListType::Table, $individualList);
        }

        if ($constants === []) {
            throw new \Exception("No <varlistentry> or <row> tags");
        }
        return $constants;
    }
}