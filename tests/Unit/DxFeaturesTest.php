<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Jengo\Pdf\Documents\Certificate;
use Jengo\Pdf\Documents\DeliveryNote;
use Jengo\Pdf\Documents\Invoice;
use Jengo\Pdf\Documents\Payslip;
use Jengo\Pdf\Documents\PurchaseOrder;
use Jengo\Pdf\Documents\Quotation;
use Jengo\Pdf\Documents\Receipt;
use Jengo\Pdf\Pdf;
use Jengo\Pdf\Support\Barcode;
use Jengo\Pdf\Support\QrCode;

class DxFeaturesTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Pdf::reset();
        parent::tearDown();
    }

    public function testPdfFakeRecordsAndAsserts(): void
    {
        $fake = Pdf::fake();
        $this->assertTrue(Pdf::isFaking());

        Pdf::assertNothingRendered();

        // 1. Download Invoice
        Pdf::template('invoice', [
            'invoiceNumber' => 'INV-9999',
            'customer'      => ['name' => 'Acme Labs'],
        ])->download('invoice-9999.pdf');

        Pdf::assertRendered('invoice');
        Pdf::assertDownloaded('invoice-9999.pdf');
        Pdf::assertViewData('invoiceNumber', 'INV-9999');
        Pdf::assertViewData('customer.name', 'Acme Labs');
        Pdf::assertCount(1);
        Pdf::assertNotDownloaded('other.pdf');
        Pdf::assertNotRendered('quotation');

        // 2. Inline Quotation
        Pdf::template('quotation', [
            'quoteNumber' => 'QUO-500',
        ])->inline('quote.pdf');

        Pdf::assertRendered('quotation');
        Pdf::assertInline('quote.pdf');
        Pdf::assertCount(2);

        // 3. Save to disk
        Pdf::html('<h1>Test Document</h1>')->save('/tmp/test-doc.pdf');
        Pdf::assertSaved('/tmp/test-doc.pdf');
        Pdf::assertCount(3);

        Pdf::reset();
        $this->assertFalse(Pdf::isFaking());
    }

    public function testPdfPreviewReturnsHtmlResponse(): void
    {
        $response = Pdf::html('<h1>Hello World</h1>')->preview();
        $body = (string) $response->getBody();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Jengo PDF Preview', $body);
        $this->assertStringContainsString('<h1>Hello World</h1>', $body);

        // Preview without toolbar
        $rawResponse = Pdf::html('<p>Raw Only</p>')->preview(withToolbar: false);
        $this->assertSame('<p>Raw Only</p>', (string) $rawResponse->getBody());
    }

    public function testToHtmlAndDataUri(): void
    {
        $doc = Pdf::html('<div>Direct Content</div>');
        $this->assertSame('<div>Direct Content</div>', $doc->toHtml());

        $dataUri = $doc->dataUri();
        $this->assertStringStartsWith('data:application/pdf;base64,', $dataUri);
    }

    public function testQrCodeAndBarcodeGeneration(): void
    {
        helper('pdf');

        // Universal <img> QR Code (Default, for Dompdf & Browser)
        $qrImg = pdf_qr_code('https://jengo.dev/verify/12345', size: 150);
        $this->assertStringContainsString('<img', $qrImg);
        $this->assertStringContainsString('src="data:image/png;base64,', $qrImg);
        $this->assertStringContainsString('width="150"', $qrImg);

        // SVG QR Code (when format is svg)
        $qrSvg = pdf_qr_code('https://jengo.dev/verify/12345', size: 150, format: 'svg');
        $this->assertStringContainsString('<svg', $qrSvg);
        $this->assertStringContainsString('width="150"', $qrSvg);
        $this->assertStringContainsString('</svg>', $qrSvg);

        // QR Code Data URI
        $qrUri = pdf_qr_data_uri('https://jengo.dev');
        $this->assertStringStartsWith('data:image/png;base64,', $qrUri);

        // Universal <img> Barcode (Default)
        $barcodeImg = pdf_barcode('PO-2026-99', height: 40);
        $this->assertStringContainsString('<img', $barcodeImg);
        $this->assertStringContainsString('src="data:image/png;base64,', $barcodeImg);

        // SVG Barcode Code 128
        $barcodeSvg = pdf_barcode('PO-2026-99', showText: true, format: 'svg');
        $this->assertStringContainsString('<svg', $barcodeSvg);
        $this->assertStringContainsString('PO-2026-99', $barcodeSvg);
        $this->assertStringContainsString('</svg>', $barcodeSvg);
    }

    public function testFluentInvoiceBuilder(): void
    {
        Pdf::fake();

        $invoice = Pdf::invoice('INV-2026-0042')
            ->customer('Wayne Enterprises', address: '1007 Mountain Drive', email: 'bruce@wayne.com')
            ->addItem('Batmobile Armor Plating', price: 50000.00, qty: 1)
            ->addItem('Grappling Hook Revision', price: 1500.00, qty: 2)
            ->taxRate(0.10)
            ->discount(2000.00)
            ->shipping(500.00)
            ->notes('Deliver to the Batcave')
            ->paid();

        $this->assertSame('invoice', $invoice->getTemplateName());
        $data = $invoice->toDataArray();

        $this->assertSame('INV-2026-0042', $data['invoiceNumber']);
        $this->assertSame('Wayne Enterprises', $data['customer']['name']);
        $this->assertCount(2, $data['items']);
        $this->assertSame('PAID', $data['status']);
        $this->assertSame(0.10, $data['taxRate']);
        $this->assertSame(2000.00, $data['discount']);

        $invoice->download('bat-invoice.pdf');
        Pdf::assertDownloaded('bat-invoice.pdf');
    }

    public function testFluentQuotationBuilder(): void
    {
        Pdf::fake();

        $quote = Pdf::quotation('QUO-777')
            ->client('Stark Industries', email: 'tony@stark.com')
            ->addItem('Arc Reactor R&D', 100000.00, qty: 1)
            ->taxRate(0.16)
            ->terms('Valid for 30 days');

        $this->assertSame('quotation', $quote->getTemplateName());
        $data = $quote->toDataArray();

        $this->assertSame('QUO-777', $data['quoteNumber']);
        $this->assertSame('Stark Industries', $data['client']['name']);
        $this->assertSame('Valid for 30 days', $data['terms']);

        $quote->inline('stark-quote.pdf');
        Pdf::assertInline('stark-quote.pdf');
    }

    public function testFluentReceiptBuilder(): void
    {
        Pdf::fake();

        $receipt = Pdf::receipt('REC-8888')
            ->customer('Peter Parker', email: 'peter@dailybugle.com')
            ->paymentMethod('M-Pesa')
            ->transactionRef('QWE891238X')
            ->addItem('Camera Lens Filter', 120.00, qty: 1)
            ->amountPaid(120.00);

        $this->assertSame('receipt', $receipt->getTemplateName());
        $data = $receipt->toDataArray();

        $this->assertSame('REC-8888', $data['receiptNumber']);
        $this->assertSame('M-Pesa', $data['paymentMethod']);
        $this->assertSame('QWE891238X', $data['transactionRef']);

        $receipt->download('receipt-8888.pdf');
        Pdf::assertDownloaded('receipt-8888.pdf');
    }

    public function testFluentDeliveryNoteBuilder(): void
    {
        Pdf::fake();

        $dn = Pdf::deliveryNote('DN-444')
            ->recipient('Daily Planet HQ', address: 'Metropolis Plaza')
            ->carrier('FastFleet Express', trackingNumber: 'FF-991823')
            ->addItem('Printing Paper Pallet', qty: 10, sku: 'PPR-A4')
            ->instructions('Leave at loading dock B');

        $this->assertSame('delivery_note', $dn->getTemplateName());
        $data = $dn->toDataArray();

        $this->assertSame('DN-444', $data['dnNumber']);
        $this->assertSame('Daily Planet HQ', $data['recipient']['name']);
        $this->assertSame('FastFleet Express', $data['carrier']['name']);
        $this->assertSame('FF-991823', $data['carrier']['tracking_number']);

        $dn->save('/tmp/dn-444.pdf');
        Pdf::assertSaved('/tmp/dn-444.pdf');
    }

    public function testFluentPayslipBuilder(): void
    {
        Pdf::fake();

        $payslip = Pdf::payslip('August 2026')
            ->employee('Clark Kent', 'EMP-001', designation: 'Senior Reporter', department: 'Newsroom')
            ->addEarning('Base Salary', 6000.00)
            ->addEarning('Investigative Bonus', 1000.00)
            ->addDeduction('Income Tax', 1400.00)
            ->addDeduction('Pension', 300.00);

        $this->assertSame('payslip', $payslip->getTemplateName());
        $data = $payslip->toDataArray();

        $this->assertSame('August 2026', $data['payPeriod']);
        $this->assertSame('Clark Kent', $data['employee']['name']);
        $this->assertCount(2, $data['earnings']);
        $this->assertCount(2, $data['deductions']);

        $payslip->download('payslip-clark.pdf');
        Pdf::assertDownloaded('payslip-clark.pdf');
    }

    public function testFluentPurchaseOrderBuilder(): void
    {
        Pdf::fake();

        $po = Pdf::purchaseOrder('PO-9912')
            ->vendor('Cyberdyne Systems', contact: 'Miles Dyson', email: 'dyson@cyberdyne.com')
            ->shipTo('Defense HQ', contact: 'Lt. Connor')
            ->addItem('Neural Network Processor', 45000.00, qty: 2, sku: 'NN-T800')
            ->paymentTerms('Net 30')
            ->shipping(1200.00);

        $this->assertSame('purchase_order', $po->getTemplateName());
        $data = $po->toDataArray();

        $this->assertSame('PO-9912', $data['poNumber']);
        $this->assertSame('Cyberdyne Systems', $data['vendor']['name']);
        $this->assertSame('Defense HQ', $data['shipTo']['name']);
        $this->assertSame('Net 30', $data['paymentTerms']);

        $po->download('po-9912.pdf');
        Pdf::assertDownloaded('po-9912.pdf');
    }

    public function testFluentCertificateBuilder(): void
    {
        Pdf::fake();

        $cert = Pdf::certificate('Certificate of Excellence')
            ->recipient('Ada Lovelace')
            ->achievement('For Pioneering Computing & Algorithms')
            ->number('CERT-0001')
            ->addSignatory('Charles Babbage', 'Director')
            ->addSignatory('Alan Turing', 'Dean of Research');

        $this->assertSame('certificate', $cert->getTemplateName());
        $data = $cert->toDataArray();

        $this->assertSame('Certificate of Excellence', $data['title']);
        $this->assertSame('Ada Lovelace', $data['recipientName']);
        $this->assertCount(2, $data['signatories']);

        $cert->download('certificate.pdf');
        Pdf::assertDownloaded('certificate.pdf');
    }

    public function testStoreAndAttachToMethods(): void
    {
        Pdf::fake();

        $doc = Pdf::html('<h1>Report</h1>');

        // Store
        $path = $doc->store('reports/monthly.pdf');
        $this->assertSame('reports/monthly.pdf', $path);
        Pdf::assertSaved();

        // AttachTo fake object
        $emailMock = new class {
            public array $attachments = [];
            public function attach(string $file, string $disposition, string $name, string $mime): void
            {
                $this->attachments[] = compact('file', 'disposition', 'name', 'mime');
            }
        };

        $doc->attachTo($emailMock, 'invoice.pdf');
        $this->assertTrue(true);
    }
}
