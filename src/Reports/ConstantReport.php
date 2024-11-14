<?php

namespace Girgias\StubToDocbook\Reports;

use Girgias\StubToDocbook\Differ\ConstantListDiff;
use Girgias\StubToDocbook\Stubs\StubConstant;

final class ConstantReport
{
    public static function generateHtmlReport(ConstantListDiff $differ, string $file): void
    {
        $fp = fopen($file, 'w');
        fputs($fp, <<<'HTML_START'
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <title>PHP Documentation Report: Constant Status</title>
</head>
<body>
 <h1>PHP Documentation Report: Constant Status</h1>
HTML_START
        );
        fputs($fp, '<p>Constants correctly documented: ' . count($differ->valid) . '</p>');

        if (count($differ->missing) > 0) {
            fputs($fp, <<<'HTML_MISSING'
<section>
 <h2>Missing Constants</h2>
HTML_MISSING);
            fputs($fp, '<p>Constants missing documentation: ' . count($differ->missing) . '</p>');
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
            foreach ($differ->missing->constants as $constant) {
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

        if (count($differ->incorrectType) > 0) {
            fputs($fp, <<<'HTML_MISSING'
<section>
 <h2>Constants with Incorrect Documented Types</h2>
HTML_MISSING);
            fputs($fp, '<p>Constants with Incorrect Documented Types: ' . count($differ->incorrectType) . '</p>');
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
            foreach ($differ->incorrectType->constants as $constant) {
                fputs($fp, self::generateHtmlReportRow($constant));
            }
            fputs($fp, <<<'HTML_MISSING_TABLE_END'
  </tbody>
 </table>
</section>
HTML_MISSING_TABLE_END);
        } else {
            fputs($fp, '<p>No constants with incorrect type</p>');
        }

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
    private static function generateHtmlReportRow(StubConstant $constant): string
    {
        return str_replace(
            ['{{NAME}}', '{{TYPE}}', '{{VALUE}}', '{{EXTENSION}}'],
            [$constant->name, $constant->type, $constant->value, $constant->extension],
            self::ROW_HTML_TEMPLATE
        );

    }
}
