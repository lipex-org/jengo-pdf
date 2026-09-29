<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use DateTimeImmutable;
use Jengo\Pdf\Schema\Column;
use Jengo\Pdf\Schema\ReportTheme;
use Jengo\Pdf\Support\Currency;
use Jengo\Pdf\Support\Margins;
use Jengo\Pdf\Support\Watermark;

class SchemaAndSupportExtendedTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        ReportTheme::clearRegistry();
        parent::tearDown();
    }

    public function testColumnFormatValueExtended(): void
    {
        // Null value
        $col = Column::make('notes');
        $this->assertSame('-', $col->formatValue(null));

        // Date formatting with DateTimeInterface
        $dateCol = Column::make('created_at')->date('d/m/Y');
        $dt = new DateTimeImmutable('2026-09-15 10:30:00');
        $this->assertSame('15/09/2026', $dateCol->formatValue($dt));

        // Datetime formatting with numeric timestamp
        $dtCol = Column::make('updated_at')->datetime('Y-m-d H:i:s');
        $ts = strtotime('2026-09-15 14:20:00');
        $this->assertSame('2026-09-15 14:20:00', $dtCol->formatValue($ts));

        // Number format with decimals
        $numCol = Column::make('qty')->number(3);
        $this->assertSame('1,250.750', $numCol->formatValue(1250.75));

        // Boolean formatting with custom labels
        $boolCol = Column::make('is_active')->boolean('Active', 'Inactive');
        $this->assertSame('Active', $boolCol->formatValue(true));
        $this->assertSame('Inactive', $boolCol->formatValue(false));

        // Fallback boolean without format
        $rawBoolCol = Column::make('flag');
        $this->assertSame('Yes', $rawBoolCol->formatValue(true));
        $this->assertSame('No', $rawBoolCol->formatValue(false));

        // Custom transformer
        $customCol = Column::make('role')->transform(function ($val, $row) {
            return strtoupper((string) $val) . ' (' . ($row['dept'] ?? 'N/A') . ')';
        });
        $this->assertSame('ADMIN (IT)', $customCol->formatValue('admin', ['dept' => 'IT']));
    }

    public function testCurrencyFormattingMatrix(): void
    {
        // Single character symbols
        $this->assertSame('$1,000.00', Currency::format(1000, '$'));
        $this->assertSame('€500.50', Currency::format(500.5, '€'));
        $this->assertSame('£75.25', Currency::format(75.25, '£'));
        $this->assertSame('¥10,000.00', Currency::format(10000, '¥'));
        $this->assertSame('₹1,500.00', Currency::format(1500, '₹'));

        // ISO codes / abbreviations with automatic space separation
        $this->assertSame('KES 1,250.00', Currency::format(1250, 'KES'));
        $this->assertSame('USD 3,400.00', Currency::format(3400, 'USD'));
        $this->assertSame('EUR 990.00', Currency::format(990, 'EUR'));
        $this->assertSame('Ksh 450.00', Currency::format(450, 'Ksh'));

        // Negative numbers
        $this->assertSame('-$250.00', Currency::format(-250, '$'));
        $this->assertSame('-KES 500.00', Currency::format(-500, 'KES'));

        // String inputs with commas or symbols
        $this->assertSame('$1,234.50', Currency::format('$1,234.50', '$'));
        $this->assertSame('$0.00', Currency::format(null, '$'));
        $this->assertSame('$0.00', Currency::format('', '$'));

        // Decimals control
        $this->assertSame('$1,000', Currency::format(1000, '$', null, 0));
        $this->assertSame('USD 1,000.000', Currency::format(1000, 'USD', null, 3));
    }

    public function testReportThemeCustomization(): void
    {
        $theme = ReportTheme::make([
            'primary'    => '#1e40af',
            'secondary'  => '#93c5fd',
            'headerText' => '#ffffff',
            'zebra'      => '#f8fafc',
            'border'     => '#e5e7eb',
            'font'       => 'Inter, sans-serif',
            'footerText' => '#6b7280',
            'customCss'  => 'body { font-size: 13px; }',
        ]);

        $this->assertSame('#1e40af', $theme->primary);
        $this->assertSame('#93c5fd', $theme->secondary);
        $this->assertSame('#ffffff', $theme->headerText);
        $this->assertSame('#f8fafc', $theme->zebra);
        $this->assertSame('#e5e7eb', $theme->border);
        $this->assertSame('Inter, sans-serif', $theme->font);
        $this->assertSame('#6b7280', $theme->footerText);
        $this->assertSame('body { font-size: 13px; }', $theme->customCss);

        // Fluent modifications
        $theme->primary('#0f766e')
            ->secondary('#14b8a6')
            ->headerText('#000000')
            ->zebra('#f0fdfa')
            ->border('#ccfbf1')
            ->font('Roboto')
            ->footerText('#99f6e4')
            ->customCss('table { width: 100%; }');

        $this->assertSame('#0f766e', $theme->primary);
        $this->assertSame('#14b8a6', $theme->secondary);
        $this->assertSame('#000000', $theme->headerText);
        $this->assertSame('#f0fdfa', $theme->zebra);
        $this->assertSame('#ccfbf1', $theme->border);
        $this->assertSame('Roboto', $theme->font);
        $this->assertSame('#99f6e4', $theme->footerText);
        $this->assertSame('table { width: 100%; }', $theme->customCss);
    }

    public function testWatermarkProperties(): void
    {
        $wm = Watermark::make('DRAFT ONLY', opacity: 0.2, color: '#e11d48', angle: -30, size: '60pt');
        $this->assertTrue($wm->enabled);
        $this->assertSame('DRAFT ONLY', $wm->text);
        $this->assertSame(0.2, $wm->opacity);
        $this->assertSame('#e11d48', $wm->color);
        $this->assertSame(-30, $wm->angle);
        $this->assertSame('60pt', $wm->size);

        $fromArray = Watermark::make([
            'text'    => 'CONFIDENTIAL',
            'opacity' => 0.15,
            'color'   => '#dc2626',
            'angle'   => 45,
            'size'    => '48pt',
            'enabled' => true,
        ]);
        $this->assertSame('CONFIDENTIAL', $fromArray->text);
        $this->assertSame(0.15, $fromArray->opacity);
        $this->assertSame('#dc2626', $fromArray->color);
        $this->assertSame(45, $fromArray->angle);
        $this->assertSame('48pt', $fromArray->size);
    }

    public function testMarginsClassParsingAndConversions(): void
    {
        $m = Margins::symmetric(15.0, 20.0, 'mm');
        $this->assertSame(15.0, $m->top);
        $this->assertSame(15.0, $m->bottom);
        $this->assertSame(20.0, $m->left);
        $this->assertSame(20.0, $m->right);
        $this->assertSame('mm', $m->unit);

        $css = $m->toCssArray();
        $this->assertSame('15mm', $css['top']);
        $this->assertSame('20mm', $css['left']);

        $pts = $m->toPoints();
        $this->assertIsFloat($pts['top']);
        $this->assertGreaterThan(0, $pts['top']);

        $zero = Margins::zero();
        $this->assertSame(0.0, $zero->top);
        $this->assertSame(0.0, $zero->right);
    }
}
