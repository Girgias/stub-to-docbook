<?php

namespace Girgias\StubToDocbook\Documentation;

use DOMElement;

class DocumentedConstantParser
{
    public static function parse(\DOMDocument $doc): DocumentedConstantList
    {
        $constants = [];

        // TODO Get title for each <variablelist> tag? (can there be multiple?)
        if (count($doc->getElementsByTagName('variablelist'))) {
            /* Extracted from gen_stub.php */
            foreach ($doc->getElementsByTagName("varlistentry") as $entry) {
                assert($entry instanceof DOMElement);

                $terms = $entry->getElementsByTagName("term");
                assert(count($terms) === 1);

                // TODO This may be false for the true/false/null constants
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

                $constants[] = new DocumentedConstant($manualConstantName, $manualType, $manualListItem);
            }
            return new DocumentedConstantList(DocumentedConstantListType::VarEntryList, $constants);
        }
        // TODO Handle multiple tables?
        if (count($doc->getElementsByTagName('table'))) {
            // TODO Parse THEAD to determine the structure of the table as those are inconsistent
            $thead = $doc->getElementsByTagName("thead")->item(0);
            $theadEntries = $thead->getElementsByTagName("row")->item(0)->getElementsByTagName("entry");
            if (count($theadEntries) !== 3) {
                // TODO Handle exoteric docs
                //echo "Table with ". count($theadEntries) . " columns\n";
                return new DocumentedConstantList(DocumentedConstantListType::Table, []);
            }
            if (str_contains($theadEntries->item(2)->textContent, 'Notes')) {
                // TODO Handle exoteric docs
                return new DocumentedConstantList(DocumentedConstantListType::Table, []);
            }

            $tbody = $doc->getElementsByTagName("tbody")->item(0);
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

                $constants[] = new DocumentedConstant($manualConstantName, $manualType, $descriptionEntry);
            }
            return new DocumentedConstantList(DocumentedConstantListType::Table, $constants);
        }

        // TODO Readline removed the varentry when the constant was removed.
        // TODO en/reference/stream/constants.xml should use a varlist instead of informaltable
        if (count($doc->getElementsByTagName('informaltable'))) {
            return new DocumentedConstantList(DocumentedConstantListType::Table, []);
        }

        throw new \Exception("No <varlistentry> or <row> tags");
    }
}