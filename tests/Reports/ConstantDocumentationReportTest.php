<?php

namespace Reports;

use Girgias\StubToDocbook\Differ\ConstantListDiff;
use Girgias\StubToDocbook\MetaData\ConstantMetaData;
use Girgias\StubToDocbook\MetaData\Lists\ConstantList;
use Girgias\StubToDocbook\Reports\ConstantDocumentationReport;
use Girgias\StubToDocbook\Types\SingleType;
use PHPUnit\Framework\TestCase;

class ConstantDocumentationReportTest extends TestCase
{
    private string $outputFile;

    protected function setUp(): void
    {
        $this->outputFile = tempnam(sys_get_temp_dir(), 'report');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->outputFile)) {
            unlink($this->outputFile);
        }
    }

    public function testEmptyReport(): void
    {
        $diff = new ConstantListDiff(
            valid: 0,
            incorrectTypes: [],
            missing: new ConstantList([]),
            incorrectIdForLinking: new ConstantList([]),
        );

        ConstantDocumentationReport::generateHtmlReport($diff, $this->outputFile);

        $html = file_get_contents($this->outputFile);
        self::assertStringContainsString('<title>PHP Documentation Report: Constant Status</title>', $html);
        self::assertStringContainsString('Constants correctly documented: 0', $html);
        self::assertStringContainsString('<p>No missing constants</p>', $html);
        self::assertStringContainsString('<p>No constants with incorrect type</p>', $html);
        self::assertStringContainsString('<p>No Documented Constants with Incorrect XML IDs for linking</p>', $html);
        self::assertStringNotContainsString('<table>', $html);
    }

    public function testReportWithMissingConstants(): void
    {
        $missing = new ConstantList([
            'MISSING_ONE' => new ConstantMetaData(
                'MISSING_ONE',
                new SingleType('int'),
                'ext_test',
                null,
                value: 42,
            ),
            'MISSING_TWO' => new ConstantMetaData(
                'MISSING_TWO',
                new SingleType('string'),
                'ext_test',
                null,
                value: 'hello',
            ),
        ]);

        $diff = new ConstantListDiff(
            valid: 5,
            incorrectTypes: [],
            missing: $missing,
            incorrectIdForLinking: new ConstantList([]),
        );

        ConstantDocumentationReport::generateHtmlReport($diff, $this->outputFile);

        $html = file_get_contents($this->outputFile);
        self::assertStringContainsString('Constants correctly documented: 5', $html);
        self::assertStringContainsString('<h2>Missing Constants</h2>', $html);
        self::assertStringContainsString('Constants missing documentation: 2', $html);
        self::assertStringContainsString('MISSING_ONE', $html);
        self::assertStringContainsString('MISSING_TWO', $html);
        self::assertStringContainsString('ext_test', $html);
        self::assertStringContainsString('<p>No constants with incorrect type</p>', $html);
        self::assertStringContainsString('<p>No Documented Constants with Incorrect XML IDs for linking</p>', $html);
    }

    public function testReportWithIncorrectTypes(): void
    {
        $incorrectTypes = [
            'BAD_TYPE_CONST' => [
                new ConstantMetaData(
                    'BAD_TYPE_CONST',
                    new SingleType('int'),
                    'ext_test',
                    'constant.bad-type-const',
                    value: 1,
                ),
                'string', // documented type (incorrect)
            ],
        ];

        $diff = new ConstantListDiff(
            valid: 3,
            incorrectTypes: $incorrectTypes,
            missing: new ConstantList([]),
            incorrectIdForLinking: new ConstantList([]),
        );

        ConstantDocumentationReport::generateHtmlReport($diff, $this->outputFile);

        $html = file_get_contents($this->outputFile);
        self::assertStringContainsString('Constants correctly documented: 3', $html);
        self::assertStringContainsString('<h2>Constants with Incorrect Documented Types</h2>', $html);
        self::assertStringContainsString('Constants with Incorrect Documented Types: 1', $html);
        self::assertStringContainsString('BAD_TYPE_CONST', $html);
        // The "Type in stub" column uses the constant's type
        self::assertStringContainsString('int', $html);
        // The "Type in docs" column uses the documented type string
        self::assertStringContainsString('string', $html);
        self::assertStringContainsString('<p>No missing constants</p>', $html);
    }

    public function testReportWithIncorrectIdsForLinking(): void
    {
        $incorrectIds = new ConstantList([
            'BAD_ID_CONST' => new ConstantMetaData(
                'BAD_ID_CONST',
                new SingleType('int'),
                'ext_test',
                'constant.bad_id_const', // wrong: uses underscores instead of hyphens
                value: 10,
            ),
        ]);

        $diff = new ConstantListDiff(
            valid: 2,
            incorrectTypes: [],
            missing: new ConstantList([]),
            incorrectIdForLinking: $incorrectIds,
        );

        ConstantDocumentationReport::generateHtmlReport($diff, $this->outputFile);

        $html = file_get_contents($this->outputFile);
        self::assertStringContainsString('Constants correctly documented: 2', $html);
        self::assertStringContainsString('<h2>Documented Constants with Incorrect XML IDs for linking</h2>', $html);
        self::assertStringContainsString('Documented Constants with Incorrect XML IDs for linking: 1', $html);
        self::assertStringContainsString('BAD_ID_CONST', $html);
        // Current ID column
        self::assertStringContainsString('constant.bad_id_const', $html);
        // Expected ID column: xmlify_labels converts underscores to hyphens and lowercases
        self::assertStringContainsString('constant.bad-id-const', $html);
        self::assertStringContainsString('<p>No missing constants</p>', $html);
        self::assertStringContainsString('<p>No constants with incorrect type</p>', $html);
    }

    public function testReportWithAllSectionsPopulated(): void
    {
        $missing = new ConstantList([
            'UNDOCUMENTED' => new ConstantMetaData(
                'UNDOCUMENTED',
                new SingleType('float'),
                'ext_math',
                null,
                value: 3.14,
            ),
        ]);

        $incorrectTypes = [
            'WRONG_TYPE' => [
                new ConstantMetaData(
                    'WRONG_TYPE',
                    new SingleType('int'),
                    'ext_core',
                    'constant.wrong-type',
                    value: 0,
                ),
                'bool',
            ],
        ];

        $incorrectIds = new ConstantList([
            'WRONG_ID' => new ConstantMetaData(
                'WRONG_ID',
                new SingleType('string'),
                'ext_core',
                'constant.wrong_id',
                value: 'test',
            ),
        ]);

        $diff = new ConstantListDiff(
            valid: 10,
            incorrectTypes: $incorrectTypes,
            missing: $missing,
            incorrectIdForLinking: $incorrectIds,
        );

        ConstantDocumentationReport::generateHtmlReport($diff, $this->outputFile);

        $html = file_get_contents($this->outputFile);

        // Header
        self::assertStringContainsString('<!DOCTYPE html>', $html);
        self::assertStringContainsString('<h1>PHP Documentation Report: Constant Status</h1>', $html);
        self::assertStringContainsString('Constants correctly documented: 10', $html);

        // Missing section
        self::assertStringContainsString('<h2>Missing Constants</h2>', $html);
        self::assertStringContainsString('Constants missing documentation: 1', $html);
        self::assertStringContainsString('UNDOCUMENTED', $html);
        self::assertStringContainsString('ext_math', $html);

        // Incorrect IDs section
        self::assertStringContainsString('<h2>Documented Constants with Incorrect XML IDs for linking</h2>', $html);
        self::assertStringContainsString('Documented Constants with Incorrect XML IDs for linking: 1', $html);
        self::assertStringContainsString('WRONG_ID', $html);
        self::assertStringContainsString('constant.wrong-id', $html);

        // Incorrect types section
        self::assertStringContainsString('<h2>Constants with Incorrect Documented Types</h2>', $html);
        self::assertStringContainsString('Constants with Incorrect Documented Types: 1', $html);
        self::assertStringContainsString('WRONG_TYPE', $html);

        // Footer
        self::assertStringContainsString('</body>', $html);
        self::assertStringContainsString('</html>', $html);
    }
}
