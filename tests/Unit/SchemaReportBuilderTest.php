<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\HTTP\ResponseInterface;
use DateTime;
use Jengo\Pdf\Contracts\PdfInterface;
use Jengo\Pdf\Enums\Orientation;
use Jengo\Pdf\Enums\PaperFormat;
use Jengo\Pdf\Pdf;
use Jengo\Pdf\Schema\Column;
use Jengo\Pdf\Schema\SchemaReportBuilder;
use Tests\TestCase;

class SchemaReportBuilderTest extends TestCase
{
    public function testColumnCreationAndFormatting(): void
    {
        $simpleCol = Column::make('name', 'Full Name');
        $this->assertSame('name', $simpleCol->key);
        $this->assertSame('Full Name', $simpleCol->label);
        $this->assertSame('John Doe', $simpleCol->formatValue('John Doe'));

        $currencyCol = Column::make('total', [
            'label'  => 'Total Price',
            'format' => 'currency:$',
            'align'  => 'right',
            'width'  => '20%',
        ]);
        $this->assertSame('$1,234.56', $currencyCol->formatValue(1234.56));
        $this->assertSame('right', $currencyCol->align);
        $this->assertSame('20%', $currencyCol->width);

        $kesCol = Column::make('total_kes', ['format' => 'currency:KES']);
        $this->assertSame('KES 1,234.56', $kesCol->formatValue(1234.56));

        $dateCol = Column::make('created_at', ['format' => 'date:Y/m/d']);
        $this->assertSame('2026/09/05', $dateCol->formatValue('2026-09-05 14:00:00'));
        $this->assertSame('2026/09/05', $dateCol->formatValue(new DateTime('2026-09-05')));

        $dateTimeCol = Column::make('updated_at', ['format' => 'datetime:Y-m-d H:i']);
        $this->assertSame('2026-09-05 14:30', $dateTimeCol->formatValue('2026-09-05 14:30:00'));

        $numberCol = Column::make('score', ['format' => 'number:2']);
        $this->assertSame('98.75', $numberCol->formatValue(98.754));

        $boolCol = Column::make('active', ['format' => 'boolean:Active|Inactive']);
        $this->assertSame('Active', $boolCol->formatValue(true));
        $this->assertSame('Inactive', $boolCol->formatValue(false));

        $rawBoolCol = Column::make('is_admin', 'Admin');
        $this->assertSame('Yes', $rawBoolCol->formatValue(true));
        $this->assertSame('No', $rawBoolCol->formatValue(false));

        $nullCol = Column::make('notes', 'Notes');
        $this->assertSame('-', $nullCol->formatValue(null));

        $customCol = Column::make('badge', [
            'transform' => fn($val) => "<span class='badge'>{$val}</span>",
        ]);
        $this->assertSame("<span class='badge'>VIP</span>", $customCol->formatValue('VIP'));
    }

    public function testSchemaReportBuilderGeneratesPdf(): void
    {
        $dataset = [
            ['id' => 1, 'customer' => 'Acme Corp', 'amount' => 500.0, 'status' => 'Paid'],
            ['id' => 2, 'customer' => 'Globex Inc', 'amount' => 750.0, 'status' => 'Pending'],
            ['id' => 3, 'customer' => 'Initech', 'amount' => 250.0, 'status' => 'Paid'],
        ];

        $report = Pdf::fromSchema($dataset)
            ->title('Sales Invoices Report')
            ->subtitle('Q3 Financial Overview')
            ->columns([
                'id'       => '# ID',
                'customer' => 'Customer Name',
                'amount'   => ['label' => 'Amount', 'format' => 'currency:USD', 'align' => 'right'],
                'status'   => 'Status',
            ])
            ->aggregate([
                'amount' => 'sum',
            ])
            ->theme('emerald')
            ->landscape()
            ->format(PaperFormat::A4);

        $pdfDoc = $report->toPdf();
        $this->assertInstanceOf(PdfInterface::class, $pdfDoc);

        $pdfBinary = $report->output();
        $this->assertNotEmpty($pdfBinary);
        $this->assertStringStartsWith('%PDF', $pdfBinary);

        $inlineResponse = $report->inline('sales-report.pdf');
        $this->assertInstanceOf(ResponseInterface::class, $inlineResponse);
        $this->assertSame('application/pdf', $inlineResponse->getHeaderLine('Content-Type'));

        $downloadResponse = $report->download('sales-report.pdf');
        $this->assertInstanceOf(ResponseInterface::class, $downloadResponse);
        $this->assertStringContainsString('attachment; filename="sales-report.pdf"', $downloadResponse->getHeaderLine('Content-Disposition'));
    }

    public function testSchemaReportBuilderAutoDetectColumnsAndAggregations(): void
    {
        $dataset = [
            ['score' => 10, 'rating' => 4.5],
            ['score' => 20, 'rating' => 3.5],
            ['score' => 30, 'rating' => 5.0],
        ];

        $report = new SchemaReportBuilder($dataset);
        $report->aggregate([
            'score'  => 'avg',
            'rating' => 'max',
        ]);

        $pdfBinary = $report->output();
        $this->assertNotEmpty($pdfBinary);
        $this->assertStringStartsWith('%PDF', $pdfBinary);
    }

    public function testSchemaReportBuilderWithObjects(): void
    {
        $obj1 = new class {
            public function toArray(): array
            {
                return ['id' => 101, 'name' => 'Widget A'];
            }
        };

        $builder = new SchemaReportBuilder($obj1);
        $pdfBinary = $builder->portrait()->format('LETTER')->output();
        $this->assertNotEmpty($pdfBinary);
        $this->assertStringStartsWith('%PDF', $pdfBinary);
    }

    public function testSchemaReportBuilderWithFindAllAndGetResultArray(): void
    {
        $modelMock = new class {
            public function findAll(): array
            {
                return [
                    ['id' => 1, 'title' => 'Article One', 'views' => 100],
                    ['id' => 2, 'title' => 'Article Two', 'views' => 200],
                ];
            }
        };

        $builder = new SchemaReportBuilder($modelMock);
        $builder->columns(['id', 'title', 'views'])
            ->aggregate(['views' => 'min']);

        $pdfBinary = $builder->output();
        $this->assertNotEmpty($pdfBinary);
        $this->assertStringStartsWith('%PDF', $pdfBinary);

        $queryMock = new class {
            public function get(): object
            {
                return new class {
                    public function getResultArray(): array
                    {
                        return [
                            ['user_id' => 5, 'name' => 'Eve', 'score' => 50],
                        ];
                    }
                };
            }
        };

        $queryBuilder = new SchemaReportBuilder($queryMock);
        $queryBuilder->aggregate(['score' => 'count']);

        $queryPdf = $queryBuilder->output();
        $this->assertNotEmpty($queryPdf);
        $this->assertStringStartsWith('%PDF', $queryPdf);
    }

    public function testSchemaReportBuilderSaveAndCustomTemplate(): void
    {
        $dataset = [
            ['id' => 1, 'name' => 'Alpha'],
        ];

        $report = new SchemaReportBuilder($dataset);
        $report->template('Tests\Views\test-view')
            ->title('Custom View Title');

        $tempPath = WRITEPATH . 'temp/schema-report-' . bin2hex(random_bytes(4)) . '.pdf';
        $saved = $report->save($tempPath);

        $this->assertSame($tempPath, $saved);
        $this->assertFileExists($tempPath);

        unlink($tempPath);
    }

    public function testSchemaReportBuilderWithEmptyDataset(): void
    {
        $report = new SchemaReportBuilder([]);
        $pdfBinary = $report->columns(['id' => '#', 'name' => 'Name'])->output();

        $this->assertNotEmpty($pdfBinary);
        $this->assertStringStartsWith('%PDF', $pdfBinary);
    }

    public function testColumnFormatValueWithArrayAndObject(): void
    {
        $col = Column::make('meta', 'Metadata');
        $this->assertSame('{"tags":["a","b"]}', $col->formatValue(['tags' => ['a', 'b']]));

        $numCol = Column::make('amount', ['format' => 'number']);
        $this->assertSame('100', $numCol->formatValue(100.4));
    }

    public function testColumnBadgeFormatting(): void
    {
        $badgeCol = Column::make('status', 'Status')->badge([
            'paid'    => 'success',
            'pending' => 'warning',
        ]);

        $formatted = $badgeCol->formatValue('paid');
        $this->assertStringContainsString('background-color:#ecfdf5', $formatted);
        $this->assertStringContainsString('color:#059669', $formatted);
        $this->assertStringContainsString('Paid', $formatted);
    }

    public function testReportThemeCustomization(): void
    {
        $theme = \Jengo\Pdf\Schema\ReportTheme::make()
            ->primary('#8b5cf6')
            ->secondary('#7c3aed')
            ->headerText('#ffffff')
            ->zebra('#f5f3ff')
            ->border('#ddd6fe')
            ->font('DejaVu Sans')
            ->footerText('#a78bfa')
            ->customCss('.title { text-transform: uppercase; }');

        $this->assertSame('#8b5cf6', $theme->primary);
        $this->assertSame('#7c3aed', $theme->secondary);
        $this->assertSame('#f5f3ff', $theme->zebra);
        $this->assertStringContainsString('.title', $theme->customCss);

        // Test SchemaReportBuilder with custom theme
        $report = new SchemaReportBuilder([['id' => 1, 'name' => 'Custom Theme Record']]);
        $pdfBinary = $report->theme($theme)->output();
        $this->assertNotEmpty($pdfBinary);
        $this->assertStringStartsWith('%PDF', $pdfBinary);
    }

    public function testReportThemeGlobalRegistration(): void
    {
        \Jengo\Pdf\Pdf::registerTheme('cyberpunk', [
            'primary'   => '#f43f5e',
            'secondary' => '#e11d48',
            'zebra'     => '#fff1f2',
            'border'    => '#fda4af',
        ]);

        $theme = \Jengo\Pdf\Schema\ReportTheme::make('cyberpunk');
        $this->assertSame('#f43f5e', $theme->primary);
        $this->assertSame('#e11d48', $theme->secondary);

        $report = new SchemaReportBuilder([['id' => 10, 'name' => 'Cyberpunk']]);
        $pdfBinary = $report->theme('cyberpunk')->output();
        $this->assertNotEmpty($pdfBinary);
    }

    public function testSchemaReportSumAggregationInHtml(): void
    {
        $dataset = [
            ['customer' => 'Alpha', 'amount' => 500.00],
            ['customer' => 'Beta',  'amount' => 750.00],
            ['customer' => 'Gamma', 'amount' => 250.00],
        ];

        $report = Pdf::fromSchema($dataset)
            ->columns([
                'customer' => 'Customer',
                'amount'   => ['label' => 'Amount', 'format' => 'currency:KES', 'sum' => true, 'align' => 'right'],
            ]);

        $html = $report->toHtml();
        $this->assertStringContainsString('SUM:', $html);
        $this->assertStringContainsString('KES 1,500.00', $html);
    }
}
