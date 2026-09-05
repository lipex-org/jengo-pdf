<?php

declare(strict_types=1);

namespace Tests\Unit;

use Jengo\Pdf\Drivers\DompdfDriver;
use Jengo\Pdf\Enums\Orientation;
use Jengo\Pdf\Enums\PaperFormat;
use Jengo\Pdf\PdfDocument;
use Jengo\Pdf\Support\Margins;
use Tests\TestCase;

class DompdfDriverTest extends TestCase
{
    public function testDompdfDriverMetadataAndAvailability(): void
    {
        $driver = new DompdfDriver();
        $this->assertSame('dompdf', $driver->getName());
        $this->assertTrue($driver->isAvailable());
    }

    public function testDompdfDriverRendersHtmlToPdf(): void
    {
        $driver = new DompdfDriver();
        $document = new PdfDocument();
        $document->html('<h1>Invoice #1001</h1><p>Customer: John Doe</p>')
            ->format(PaperFormat::A4)
            ->orientation(Orientation::PORTRAIT)
            ->margins(Margins::all(15, 'mm'));

        $pdfOutput = $driver->render($document);

        $this->assertNotEmpty($pdfOutput);
        $this->assertStringStartsWith('%PDF', $pdfOutput);
    }

    public function testDompdfDriverRendersLandscapeAndLetter(): void
    {
        $driver = new DompdfDriver();
        $document = new PdfDocument();
        $document->html('<h1>Landscape Report</h1>')
            ->format(PaperFormat::LETTER)
            ->landscape();

        $pdfOutput = $driver->render($document);

        $this->assertNotEmpty($pdfOutput);
        $this->assertStringStartsWith('%PDF', $pdfOutput);
    }

    public function testDompdfDriverRendersFromView(): void
    {
        $driver = new DompdfDriver();
        $document = new PdfDocument();
        $document->view('Tests\Views\test-view', ['title' => 'Invoice Title', 'content' => 'Invoice Body']);

        $pdfOutput = $driver->render($document);
        $this->assertNotEmpty($pdfOutput);
        $this->assertStringStartsWith('%PDF', $pdfOutput);
    }

    public function testDompdfDriverRendersWithNullMargins(): void
    {
        $driver = new DompdfDriver();
        $document = new PdfDocument();
        $ref = new \ReflectionClass($document);
        $marginsProp = $ref->getProperty('margins');
        $marginsProp->setAccessible(true);
        $marginsProp->setValue($document, null);

        $document->html('<h1>No Margin</h1>');
        $pdfOutput = $driver->render($document);
        $this->assertNotEmpty($pdfOutput);
    }

    public function testDompdfDriverThrowsWhenNotAvailable(): void
    {
        $driver = new class extends DompdfDriver {
            public function isAvailable(): bool
            {
                return false;
            }
        };

        $this->expectException(\Jengo\Pdf\Exceptions\DriverException::class);
        $this->expectExceptionMessage('Dompdf library is not installed');

        $doc = new PdfDocument();
        $doc->html('<h1>Test</h1>');
        $driver->render($doc);
    }
}
