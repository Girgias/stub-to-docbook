<?php

namespace Girgias\StubToDocbook\Reports;

use Girgias\StubToDocbook\Differ\ConstantListDiff;
use Girgias\StubToDocbook\Documentation\DocumentedConstantList;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\Stubs\StubConstantList;

final class ConstantReport
{
    public static function generateHtmlReport(ConstantListDiff $differ, string $file): void
    {
        $fp = fopen($file, 'w');
        if ($fp === false) {
            throw new \RuntimeException("Cannot open file \"$file\".");
        }
        fputs(
            $fp,
            <<<'HTML_START'
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <title>PHP Documentation Report: Constant Status</title>
</head>
<body>
 <h1>PHP Documentation Report: Constant Status</h1>
HTML_START,
        );
        fputs($fp, '<p>Constants correctly documented: ' . $differ->valid . '</p>');

        self::generateHtmlReportMissingConstants($fp, $differ->missing);
        self::generateHtmlReportIncorrectDocumentedConstantIdsForLinking($fp, $differ->incorrectIdForLinking);
        self::generateHtmlReportIncorrectConstantTypes($fp, $differ->incorrectTypes);

        fputs($fp, <<<'HTML_END'
</body>
</html>
HTML_END);
    }

    private const ROW_HTML_TEMPLATE = <<<'HTML'
    <tr>
      <th scope="row">{{NAME}}</th>
      <td>{{TYPE}}</td>
      <td>{{VALUE}}</td>
      <td>{{EXTENSION}}</td>
    </tr>
HTML;
    private static function generateHtmlReportRow(ConstantMetaData $constant): string
    {
        return str_replace(
            ['{{NAME}}', '{{TYPE}}', '{{VALUE}}', '{{EXTENSION}}'],
            /**
             * $constant->value is of type float|int|string|null, which is safe to cast to string.
             * do this explicitly so that phpstan stops complaining.
             */
            [$constant->name, $constant->type->name, (string) $constant->value, $constant->extension],
            self::ROW_HTML_TEMPLATE,
        );

    }

    /**
     * @param resource $fp
     */
    private static function generateHtmlReportMissingConstants($fp, StubConstantList $constants): void
    {
        if (count($constants) > 0) {
            fputs($fp, <<<'HTML_MISSING'
<section>
 <h2>Missing Constants</h2>
HTML_MISSING);
            fputs($fp, '<p>Constants missing documentation: ' . count($constants) . '</p>');
            fputs($fp, <<<'HTML_MISSING_TABLE_START'
 <table>
  <thead>
    <tr>
      <th scope="col">Name</th>
      <th scope="col">Type</th>
      <th scope="col">Value</th>
      <th scope="col">Extension</th>
    </tr>
  </thead>
  <tbody>
HTML_MISSING_TABLE_START);
            foreach ($constants->constants as $constant) {
                fputs($fp, self::generateHtmlReportRow($constant));
            }
            fputs($fp, <<<'HTML_MISSING_TABLE_END'
  </tbody>
 </table>
</section>
HTML_MISSING_TABLE_END);
        } else {
            fputs($fp, '<p>No missing constants</p>');
        }
    }

    /**
     * @param resource $fp
     * @param array<string, array{0: ConstantMetaData, 1: string}> $incorrectTypes
     */
    private static function generateHtmlReportIncorrectConstantTypes($fp, array $incorrectTypes): void
    {
        if (count($incorrectTypes) > 0) {
            fputs($fp, <<<'HTML_MISSING'
<section>
 <h2>Constants with Incorrect Documented Types</h2>
HTML_MISSING);
            fputs($fp, '<p>Constants with Incorrect Documented Types: ' . count($incorrectTypes) . '</p>');
            fputs($fp, <<<'HTML_MISSING_TABLE_START'
 <table>
  <thead>
    <tr>
      <th scope="col">Name</th>
      <th scope="col">Type in stub</th>
      <th scope="col">Type in docs</th>
      <th scope="col">Extension</th>
    </tr>
  </thead>
  <tbody>
HTML_MISSING_TABLE_START);
            foreach ($incorrectTypes as $constantWrapper) {
                $constant = $constantWrapper[0];
                $row = str_replace(
                    ['{{NAME}}', '{{TYPE}}', '{{VALUE}}', '{{EXTENSION}}'],
                    [$constant->name, $constant->type->name, $constantWrapper[1], $constant->extension],
                    self::ROW_HTML_TEMPLATE,
                );
                fputs($fp, $row);
            }
            fputs($fp, <<<'HTML_MISSING_TABLE_END'
  </tbody>
 </table>
</section>
HTML_MISSING_TABLE_END);
        } else {
            fputs($fp, '<p>No constants with incorrect type</p>');
        }
    }

    /**
     * @param resource $fp
     */
    private static function generateHtmlReportIncorrectDocumentedConstantIdsForLinking($fp, DocumentedConstantList $incorrectIdsForLinking): void
    {
        if (count($incorrectIdsForLinking) > 0) {
            fputs($fp, <<<'HTML_MISSING'
<section>
 <h2>Documented Constants with Incorrect XML IDs for linking</h2>
HTML_MISSING);
            fputs($fp, '<p>Documented Constants with Incorrect XML IDs for linking: ' . count($incorrectIdsForLinking) . '</p>');
            fputs($fp, <<<'HTML_MISSING_TABLE_START'
 <table>
  <thead>
    <tr>
      <th scope="col">Name</th>
      <th scope="col">Current ID</th>
      <th scope="col">Expected ID for linking</th>
      <th scope="col">Unused</th>
    </tr>
  </thead>
  <tbody>
HTML_MISSING_TABLE_START);
            foreach ($incorrectIdsForLinking->constants as $constant) {
                $row = str_replace(
                    ['{{NAME}}', '{{TYPE}}', '{{VALUE}}', '{{EXTENSION}}'],
                    [$constant->name, $constant->id ?? 'NO ID', 'constant.' . xmlify_labels($constant->name), ''],
                    self::ROW_HTML_TEMPLATE,
                );
                fputs($fp, $row);
            }
            fputs($fp, <<<'HTML_MISSING_TABLE_END'
  </tbody>
 </table>
</section>
HTML_MISSING_TABLE_END);
        } else {
            fputs($fp, '<p>No Documented Constants with Incorrect XML IDs for linking</p>');
        }
    }
}
