<?php

declare(strict_types=1);

namespace Tests\Unit;

use Jengo\Pdf\Enums\MediaType;
use Jengo\Pdf\Enums\Orientation;
use Jengo\Pdf\Enums\PaperFormat;
use Jengo\Pdf\Support\HeaderFooter;
use Jengo\Pdf\Support\Margins;
use Tests\TestCase;

class EnumsAndSupportTest extends TestCase
{
    public function testPaperFormatEnum(): void
    {
        $this->assertSame('A4', PaperFormat::A4->value);
        $this->assertSame('Letter', PaperFormat::LETTER->value);

        $a4Points = PaperFormat::A4->getDimensionsInPoints();
        $this->assertEqualsWithDelta(595.28, $a4Points[0], 0.1);
        $this->assertEqualsWithDelta(841.89, $a4Points[1], 0.1);

        $a4Mm = PaperFormat::A4->getDimensionsInMillimeters();
        $this->assertSame(210.0, $a4Mm[0]);
        $this->assertSame(297.0, $a4Mm[1]);

        $letterPoints = PaperFormat::LETTER->getDimensionsInPoints();
        $this->assertSame(612.0, $letterPoints[0]);
        $this->assertSame(792.0, $letterPoints[1]);
    }

    public function testOrientationEnum(): void
    {
        $portrait = Orientation::PORTRAIT;
        $landscape = Orientation::LANDSCAPE;

        $this->assertTrue($portrait->isPortrait());
        $this->assertFalse($portrait->isLandscape());

        $this->assertTrue($landscape->isLandscape());
        $this->assertFalse($landscape->isPortrait());
    }

    public function testMediaTypeEnum(): void
    {
        $this->assertSame('screen', MediaType::SCREEN->value);
        $this->assertSame('print', MediaType::PRINT->value);
    }

    public function testMarginsClass(): void
    {
        $margins = new Margins(15.0, 10.0, 15.0, 10.0, 'mm');
        $this->assertSame(15.0, $margins->top);
        $this->assertSame(10.0, $margins->right);
        $this->assertSame(15.0, $margins->bottom);
        $this->assertSame(10.0, $margins->left);

        $css = $margins->toCssArray();
        $this->assertSame('15mm', $css['top']);
        $this->assertSame('10mm', $css['right']);

        $all = Margins::all(20.0, 'in');
        $this->assertSame(20.0, $all->top);
        $this->assertSame(20.0, $all->left);
        $this->assertSame('in', $all->unit);

        $zero = Margins::zero();
        $this->assertSame(0.0, $zero->top);

        $symm = Margins::symmetric(12.0, 8.0, 'cm');
        $this->assertSame(12.0, $symm->top);
        $this->assertSame(8.0, $symm->right);
        $this->assertSame(12.0, $symm->bottom);
        $this->assertSame(8.0, $symm->left);

        // Test toPoints for different units
        $ptMargins = new Margins(10, 10, 10, 10, 'pt');
        $this->assertSame(10.0, $ptMargins->toPoints()['top']);

        $inMargins = new Margins(1, 1, 1, 1, 'in');
        $this->assertSame(72.0, $inMargins->toPoints()['top']);

        $cmMargins = new Margins(1, 1, 1, 1, 'cm');
        $this->assertEqualsWithDelta(28.3465, $cmMargins->toPoints()['top'], 0.01);

        $pxMargins = new Margins(96, 96, 96, 96, 'px');
        $this->assertEqualsWithDelta(72.0, $pxMargins->toPoints()['top'], 0.01);
    }

    public function testAllPaperFormats(): void
    {
        $cases = PaperFormat::cases();
        foreach ($cases as $case) {
            $points = $case->getDimensionsInPoints();
            $mm = $case->getDimensionsInMillimeters();
            $this->assertCount(2, $points);
            $this->assertCount(2, $mm);
            $this->assertGreaterThan(0, $points[0]);
            $this->assertGreaterThan(0, $points[1]);
            $this->assertGreaterThan(0, $mm[0]);
            $this->assertGreaterThan(0, $mm[1]);

            $fromName = PaperFormat::fromName($case->name);
            $this->assertSame($case, $fromName);
        }
    }

    public function testMarginsFallbackUnit(): void
    {
        $unknownMargins = new Margins(10, 10, 10, 10, 'unknown');
        $pts = $unknownMargins->toPoints();
        $this->assertEqualsWithDelta(28.3465, $pts['top'], 0.01);
    }

    public function testHeaderFooterClass(): void
    {
        $fromHtml = HeaderFooter::fromHtml('<div>Header</div>', 20.0, 'mm');
        $this->assertSame('<div>Header</div>', $fromHtml->renderHtml());

        $pageNums = HeaderFooter::pageNumbers('Page {page} of {pages}');
        $this->assertStringContainsString('Page {page} of {pages}', $pageNums->renderHtml());

        $empty = new HeaderFooter();
        $this->assertSame('', $empty->renderHtml());
    }

    public function testHeaderFooterFromView(): void
    {
        $viewHeader = HeaderFooter::fromView('Tests\Views\test-view', ['title' => 'Custom Header']);
        $rendered = $viewHeader->renderHtml();
        $this->assertStringContainsString('Custom Header', $rendered);
    }

    public function testCurrencySupportClass(): void
    {
        // Symbols
        $this->assertSame('$1,234.56', \Jengo\Pdf\Support\Currency::format(1234.56, '$'));
        $this->assertSame('€1,234.56', \Jengo\Pdf\Support\Currency::format(1234.56, '€'));
        $this->assertSame('£1,234.56', \Jengo\Pdf\Support\Currency::format(1234.56, '£'));

        // ISO Currency Codes & Abbreviations
        $this->assertSame('KES 1,234.56', \Jengo\Pdf\Support\Currency::format(1234.56, 'KES'));
        $this->assertSame('USD 1,234.56', \Jengo\Pdf\Support\Currency::format(1234.56, 'USD'));
        $this->assertSame('EUR 1,234.56', \Jengo\Pdf\Support\Currency::format(1234.56, 'EUR'));
        $this->assertSame('Ksh 1,234.56', \Jengo\Pdf\Support\Currency::format(1234.56, 'Ksh'));

        // Sanitizing trailing/leading spaces from input
        $this->assertSame('KES 1,234.56', \Jengo\Pdf\Support\Currency::format(1234.56, 'KES '));
        $this->assertSame('KES 1,234.56', \Jengo\Pdf\Support\Currency::format(1234.56, ' KES '));
        $this->assertSame('$1,234.56', \Jengo\Pdf\Support\Currency::format(1234.56, ' $ '));

        // Negative numbers
        $this->assertSame('-$1,234.56', \Jengo\Pdf\Support\Currency::format(-1234.56, '$'));
        $this->assertSame('-KES 1,234.56', \Jengo\Pdf\Support\Currency::format(-1234.56, 'KES'));

        // Helper function
        helper('pdf');
        $this->assertSame('KES 500.00', pdf_currency(500, 'KES'));
        $this->assertSame('$500.00', pdf_currency(500, '$'));

        // Parsing
        $this->assertSame(1234.56, \Jengo\Pdf\Support\Currency::parse('KES 1,234.56'));
        $this->assertSame(1234.56, \Jengo\Pdf\Support\Currency::parse('$1,234.56'));
        $this->assertSame(-500.0, \Jengo\Pdf\Support\Currency::parse('-KES 500.00'));
    }

    public function testWatermarkSupportClass(): void
    {
        // Default text is 'JENGO'
        $wmDefault = \Jengo\Pdf\Support\Watermark::make();
        $this->assertSame('JENGO', $wmDefault->text);
        $this->assertSame(0.08, $wmDefault->opacity);
        $this->assertSame('#64748b', $wmDefault->color);
        $this->assertSame(-35, $wmDefault->angle);
        $this->assertSame('64pt', $wmDefault->size);
        $this->assertTrue($wmDefault->enabled);

        // Custom string
        $wmCustom = \Jengo\Pdf\Support\Watermark::make('CONFIDENTIAL', 0.15, '#dc2626', -45, '72pt');
        $this->assertSame('CONFIDENTIAL', $wmCustom->text);
        $this->assertSame(0.15, $wmCustom->opacity);
        $this->assertSame('#dc2626', $wmCustom->color);
        $this->assertSame(-45, $wmCustom->angle);
        $this->assertSame('72pt', $wmCustom->size);

        // Array config
        $wmArray = \Jengo\Pdf\Support\Watermark::make([
            'text'    => 'DRAFT',
            'opacity' => 0.1,
            'color'   => '#0284c7',
            'angle'   => -30,
            'size'    => '50pt',
            'enabled' => true,
        ]);
        $this->assertSame('DRAFT', $wmArray->text);
        $this->assertSame(0.1, $wmArray->opacity);
        $this->assertSame('#0284c7', $wmArray->color);
        $this->assertSame(-30, $wmArray->angle);
        $this->assertSame('50pt', $wmArray->size);
        $this->assertTrue($wmArray->enabled);

        // Disabled boolean
        $wmDisabled = \Jengo\Pdf\Support\Watermark::make(false);
        $this->assertFalse($wmDisabled->enabled);
        $this->assertSame('', $wmDisabled->renderHtml());
        $this->assertSame('', $wmDisabled->renderCss());

        // HTML rendering
        $html = $wmCustom->renderHtml();
        $this->assertStringContainsString('CONFIDENTIAL', $html);
        $this->assertStringContainsString('class="jengo-watermark"', $html);
        $this->assertStringContainsString('rotate(-45deg)', $html);
        $this->assertStringContainsString('opacity: 0.15', $html);

        // Helper function
        helper('pdf');
        $helperHtml = pdf_watermark('JENGO');
        $this->assertStringContainsString('JENGO', $helperHtml);
        $this->assertStringContainsString('jengo-watermark', $helperHtml);
    }
}
