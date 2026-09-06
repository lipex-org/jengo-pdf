<?php

declare(strict_types=1);

namespace Tests\Unit;

use Jengo\Pdf\Config\Registrar;
use Jengo\Pdf\Config\Services;
use Jengo\Pdf\Contracts\PdfInterface;
use Jengo\Pdf\Contracts\SchemaReportInterface;
use Jengo\Pdf\Installers\PdfInstaller;
use Jengo\Pdf\Pdf;
use Jengo\Pdf\PdfDocument;
use Tests\TestCase;

class PdfFacadeAndHelperTest extends TestCase
{
    public function testPdfFacadeMethods(): void
    {
        $htmlDoc = Pdf::html('<h1>Hello</h1>');
        $this->assertInstanceOf(PdfInterface::class, $htmlDoc);

        $urlDoc = Pdf::url('https://example.com');
        $this->assertInstanceOf(PdfInterface::class, $urlDoc);

        $driverDoc = Pdf::driver('dompdf');
        $this->assertInstanceOf(PdfInterface::class, $driverDoc);

        $schemaBuilder = Pdf::fromSchema([['id' => 1, 'name' => 'Alice']]);
        $this->assertInstanceOf(SchemaReportInterface::class, $schemaBuilder);

        $viewDoc = Pdf::view('Tests\Views\test-view', ['title' => 'PDF View']);
        $this->assertInstanceOf(PdfInterface::class, $viewDoc);

        $newDoc = Pdf::newDocument();
        $this->assertInstanceOf(PdfDocument::class, $newDoc);

        // Test Built-in Templates
        $templates = ['invoice', 'quotation', 'receipt', 'delivery_note', 'payslip', 'purchase_order', 'certificate'];
        foreach ($templates as $tpl) {
            $tplDoc = Pdf::template($tpl, ['company' => ['name' => 'Test Corp']]);
            $this->assertInstanceOf(PdfInterface::class, $tplDoc);
            $output = $tplDoc->output();
            $this->assertNotEmpty($output);
            $this->assertStringStartsWith('%PDF', $output);
        }
    }

    public function testTemplateThrowsExceptionForUnknownTemplate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Pdf::template('non_existent_template_12345');
    }

    public function testPdfHelperFunction(): void
    {
        helper('pdf');

        $doc = pdf();
        $this->assertInstanceOf(PdfInterface::class, $doc);

        $viewDoc = pdf('Tests\Views\test-view', ['title' => 'Helper View']);
        $this->assertInstanceOf(PdfInterface::class, $viewDoc);
    }

    public function testServicesPdf(): void
    {
        $serviceDoc = Services::pdf();
        $this->assertInstanceOf(PdfDocument::class, $serviceDoc);

        $sharedDoc1 = Services::pdf(true);
        $sharedDoc2 = Services::pdf(true);
        $this->assertSame($sharedDoc1, $sharedDoc2);
    }

    public function testRegistrarJengoBase(): void
    {
        $registered = Registrar::JengoBase();
        $this->assertArrayHasKey('installers', $registered);
        $this->assertContains(PdfInstaller::class, $registered['installers']);
    }

    public function testPdfInstaller(): void
    {
        $this->assertSame('pdf', PdfInstaller::name());
        $this->assertNotEmpty(PdfInstaller::description());
        $this->assertNotEmpty(PdfInstaller::reasonForSkipping());

        $installer = new PdfInstaller();
        $this->assertIsBool($installer->shouldRun());

        // Test install
        $targetFile = APPPATH . 'Config/Pdf.php';
        $hadFile = file_exists($targetFile);
        $backup = $hadFile ? file_get_contents($targetFile) : null;

        if ($hadFile) {
            unlink($targetFile);
        }

        $installer->install();
        $this->assertSame(1, $installer->runs);
        $this->assertFileExists($targetFile);

        // Run again when exists
        $installer->install();
        $this->assertSame(2, $installer->runs);

        // Clean up
        if ($hadFile && $backup !== null) {
            file_put_contents($targetFile, $backup);
        } else {
            @unlink($targetFile);
        }
    }

    public function testTemplateResolvesConfiguredCustomView(): void
    {
        $customConfig = new \Jengo\Pdf\Config\Pdf();
        $customConfig->templating['views']['custom_invoice'] = 'Tests\Views\test-view';

        $doc = new PdfDocument($customConfig);
        $doc->template('custom_invoice', ['title' => 'Custom Invoice Title']);
        $this->assertSame('Tests\Views\test-view', $doc->getView());
    }
}
