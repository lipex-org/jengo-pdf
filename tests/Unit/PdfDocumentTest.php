<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\HTTP\ResponseInterface;
use Jengo\Pdf\Contracts\DriverInterface;
use Jengo\Pdf\Enums\MediaType;
use Jengo\Pdf\Enums\Orientation;
use Jengo\Pdf\Enums\PaperFormat;
use Jengo\Pdf\Exceptions\DriverException;
use Jengo\Pdf\PdfDocument;
use Jengo\Pdf\Support\Margins;
use Tests\TestCase;

class PdfDocumentTest extends TestCase
{
    public function testFluentSettersAndGetters(): void
    {
        $doc = new PdfDocument();
        $doc->html('<p>Hello</p>')
            ->format(PaperFormat::A4)
            ->orientation(Orientation::PORTRAIT)
            ->margins(12, 12, 12, 12, 'mm')
            ->scale(0.9)
            ->background(true)
            ->emulateMedia(MediaType::SCREEN)
            ->header('<div>Header</div>')
            ->footer('<div>Footer</div>')
            ->waitForSelector('#ready')
            ->waitForTimeout(500);

        $this->assertSame('<p>Hello</p>', $doc->getHtml());
        $this->assertSame(PaperFormat::A4, $doc->getFormat());
        $this->assertSame(Orientation::PORTRAIT, $doc->getOrientation());
        $this->assertSame(12.0, $doc->getMargins()->top);
        $this->assertSame(0.9, $doc->getScale());
        $this->assertTrue($doc->hasBackground());
        $this->assertSame(MediaType::SCREEN, $doc->getMediaType());
        $this->assertSame('#ready', $doc->getWaitForSelector());
        $this->assertSame(500, $doc->getWaitForTimeout());
        $this->assertNotNull($doc->getHeader());
        $this->assertNotNull($doc->getFooter());
    }

    public function testStringFormatAndOrientationSetters(): void
    {
        $doc = new PdfDocument();
        $doc->format('LETTER')
            ->orientation('landscape')
            ->emulateMedia('print');

        $this->assertSame(PaperFormat::LETTER, $doc->getFormat());
        $this->assertSame(Orientation::LANDSCAPE, $doc->getOrientation());
        $this->assertSame(MediaType::PRINT, $doc->getMediaType());

        $doc->portrait();
        $this->assertSame(Orientation::PORTRAIT, $doc->getOrientation());

        $doc->landscape();
        $this->assertSame(Orientation::LANDSCAPE, $doc->getOrientation());
    }

    public function testCustomDriverInjection(): void
    {
        $mockDriver = new class implements DriverInterface {
            public function render(PdfDocument $document): string
            {
                return '%PDF-1.4 Mock Binary Content';
            }
            public function isAvailable(): bool
            {
                return true;
            }
            public function getName(): string
            {
                return 'mock';
            }
        };

        $doc = new PdfDocument();
        $doc->driver($mockDriver)->html('<p>Test</p>');

        $output = $doc->output();
        $this->assertSame('%PDF-1.4 Mock Binary Content', $output);

        $base64 = $doc->base64();
        $this->assertSame(base64_encode('%PDF-1.4 Mock Binary Content'), $base64);
    }

    public function testSavePdfToFile(): void
    {
        $mockDriver = new class implements DriverInterface {
            public function render(PdfDocument $document): string
            {
                return '%PDF-1.4 Saved Content';
            }
            public function isAvailable(): bool
            {
                return true;
            }
            public function getName(): string
            {
                return 'mock';
            }
        };

        $doc = new PdfDocument();
        $doc->driver($mockDriver)->html('<h1>Save Test</h1>');

        $tempPath = WRITEPATH . 'temp/test-output-' . bin2hex(random_bytes(4)) . '.pdf';
        $savedPath = $doc->save($tempPath);

        $this->assertSame($tempPath, $savedPath);
        $this->assertFileExists($tempPath);
        $this->assertSame('%PDF-1.4 Saved Content', file_get_contents($tempPath));

        unlink($tempPath);
    }

    public function testInlineAndDownloadResponses(): void
    {
        $mockDriver = new class implements DriverInterface {
            public function render(PdfDocument $document): string
            {
                return '%PDF-1.4 Download Content';
            }
            public function isAvailable(): bool
            {
                return true;
            }
            public function getName(): string
            {
                return 'mock';
            }
        };

        $doc = new PdfDocument();
        $doc->driver($mockDriver)->html('<h1>Test</h1>');

        $inlineRes = $doc->inline('report.pdf');
        $this->assertInstanceOf(ResponseInterface::class, $inlineRes);
        $this->assertSame('application/pdf', $inlineRes->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('inline; filename="report.pdf"', $inlineRes->getHeaderLine('Content-Disposition'));

        $downloadRes = $doc->download('invoice');
        $this->assertInstanceOf(ResponseInterface::class, $downloadRes);
        $this->assertStringContainsString('attachment; filename="invoice.pdf"', $downloadRes->getHeaderLine('Content-Disposition'));
    }

    public function testUnknownDriverThrowsException(): void
    {
        $doc = new PdfDocument();
        $doc->driver('unsupported_driver');

        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('Unknown PDF driver [unsupported_driver]');

        $doc->output();
    }

    public function testViewAndUrlAndHeaderFooterViews(): void
    {
        $doc = new PdfDocument();
        $doc->view('Tests\Views\test-view', ['title' => 'Doc Title', 'content' => 'Doc Content'])
            ->headerView('Tests\Views\test-view', ['title' => 'Header View'])
            ->footerView('Tests\Views\test-view', ['title' => 'Footer View'])
            ->margins(Margins::symmetric(10, 15, 'mm'));

        $this->assertSame('Tests\Views\test-view', $doc->getView());
        $this->assertSame(['title' => 'Doc Title', 'content' => 'Doc Content'], $doc->getViewData());
        $this->assertNull($doc->getHtml());
        $this->assertNull($doc->getUrl());
        $this->assertSame(10.0, $doc->getMargins()->top);
        $this->assertSame(15.0, $doc->getMargins()->right);

        $doc->url('https://example.com/test');
        $this->assertSame('https://example.com/test', $doc->getUrl());
        $this->assertNull($doc->getView());
        $this->assertNull($doc->getHtml());

        $doc->pageNumbers('P. {page} / {pages}');
        $this->assertNotNull($doc->getFooter());
    }
}
