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
use Jengo\Pdf\Enums\Orientation;
use Jengo\Pdf\Enums\PaperFormat;
use Jengo\Pdf\Pdf;

class DocumentBuildersExtendedTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Pdf::reset();
        parent::tearDown();
    }

    public function testInvoiceFluentBuilderAndCalculations(): void
    {
        $invoice = Invoice::make('INV-2026-001')
            ->date('2026-10-01')
            ->dueDate('2026-10-15')
            ->paid()
            ->customer('Acme Corp', '123 Tech Blvd', 'billing@acme.com', '+1-555-0199', 'TAX-9988')
            ->addItem('Cloud Hosting Plan', 150.00, 2, 'Monthly high-performance tier')
            ->addItem('Domain Registration', 20.50, 1)
            ->taxRate(16.0)
            ->discount(10.0)
            ->shipping(15.0)
            ->notes('Thank you for your business!')
            ->primaryColor('#4f46e5')
            ->fontFamily('Helvetica')
            ->currency('USD')
            ->dateFormat('d M Y')
            ->footerText('Payment due within 15 days')
            ->showPoweredBy(false)
            ->company('TechCorp Inc', 'NextGen Solutions', 'PO Box 100', 'info@techcorp.io', '+1-555-1000', 'VAT-1122');

        $this->assertSame('invoice', $invoice->getTemplateName());

        $data = $invoice->toDataArray();
        $this->assertSame('INV-2026-001', $data['invoiceNumber']);
        $this->assertSame('2026-10-01', $data['invoiceDate']);
        $this->assertSame('2026-10-15', $data['dueDate']);
        $this->assertSame('PAID', $data['status']);
        $this->assertSame(16.0, $data['taxRate']);
        $this->assertSame(10.0, $data['discount']);
        $this->assertSame(15.0, $data['shipping']);
        $this->assertSame('Thank you for your business!', $data['notes']);

        // Customer array
        $this->assertSame('Acme Corp', $data['customer']['name']);
        $this->assertSame('123 Tech Blvd', $data['customer']['address']);
        $this->assertSame('billing@acme.com', $data['customer']['email']);
        $this->assertSame('+1-555-0199', $data['customer']['phone']);
        $this->assertSame('TAX-9988', $data['customer']['tax_id']);

        // Items calculations
        $this->assertCount(2, $data['items']);
        $this->assertSame(300.0, $data['items'][0]['total']);
        $this->assertSame(20.50, $data['items'][1]['total']);

        // Company and brand overrides
        $this->assertSame('TechCorp Inc', $data['company']['name']);
        $this->assertSame('#4f46e5', $data['primaryColor']);
        $this->assertSame('Helvetica', $data['fontFamily']);
        $this->assertSame('USD', $data['currency']);
        $this->assertSame('d M Y', $data['dateFormat']);
        $this->assertSame('Payment due within 15 days', $data['footerText']);
        $this->assertFalse($data['showPoweredBy']);

        // Test status helpers
        $invoice->unpaid();
        $this->assertSame('UNPAID', $invoice->toDataArray()['status']);

        $invoice->overdue();
        $this->assertSame('OVERDUE', $invoice->toDataArray()['status']);

        $invoice->status('draft');
        $this->assertSame('DRAFT', $invoice->toDataArray()['status']);

        // Bulk items override
        $invoice->items([
            ['name' => 'Custom Item', 'price' => 50.0, 'qty' => 3, 'total' => 150.0],
        ]);
        $this->assertCount(1, $invoice->toDataArray()['items']);
    }

    public function testReceiptFluentBuilderAndData(): void
    {
        $receipt = Receipt::make('REC-8877')
            ->date('2026-10-02')
            ->paymentMethod('M-Pesa')
            ->transactionRef('MPESA-TX-998811')
            ->customer('John Doe', 'john@example.com', '+254700000000', 'Nairobi, Kenya')
            ->addItem('Consultancy Service', 250.00, 1)
            ->taxRate(0.0)
            ->amountPaid(250.00);

        $this->assertSame('receipt', $receipt->getTemplateName());

        $data = $receipt->toDataArray();
        $this->assertSame('REC-8877', $data['receiptNumber']);
        $this->assertSame('2026-10-02', $data['receiptDate']);
        $this->assertSame('M-Pesa', $data['paymentMethod']);
        $this->assertSame('MPESA-TX-998811', $data['transactionRef']);
        $this->assertSame(250.00, $data['amountPaid']);
        $this->assertSame('John Doe', $data['customer']['name']);
        $this->assertSame('john@example.com', $data['customer']['email']);
        $this->assertSame('+254700000000', $data['customer']['phone']);
        $this->assertSame('Nairobi, Kenya', $data['customer']['address']);
        $this->assertCount(1, $data['items']);
        $this->assertSame(250.00, $data['items'][0]['total']);

        // Number and bulk items override
        $receipt->number('REC-9999');
        $receipt->items([['name' => 'Item X', 'price' => 10.0, 'qty' => 2, 'total' => 20.0]]);
        $data2 = $receipt->toDataArray();
        $this->assertSame('REC-9999', $data2['receiptNumber']);
        $this->assertCount(1, $data2['items']);
    }

    public function testQuotationFluentBuilderAndData(): void
    {
        $quote = Quotation::make('QUO-7788')
            ->date('2026-10-05')
            ->validUntil('2026-11-05')
            ->client('Global Logistics LLC', '45 Port Way', 'ops@globallogistics.com', '+1-800-555-0123')
            ->addItem('Custom Software Development', 120.00, 40, '40 hours of frontend/backend dev')
            ->addItem('QA Testing & Integration', 90.00, 10, '10 hours QA and staging deployment')
            ->taxRate(10.0)
            ->discount(150.0)
            ->terms('50% advance on signing, 50% on project delivery.');

        $this->assertSame('quotation', $quote->getTemplateName());

        $data = $quote->toDataArray();
        $this->assertSame('QUO-7788', $data['quoteNumber']);
        $this->assertSame('2026-10-05', $data['quoteDate']);
        $this->assertSame('2026-11-05', $data['validUntil']);
        $this->assertSame('Global Logistics LLC', $data['client']['name']);
        $this->assertSame('45 Port Way', $data['client']['address']);
        $this->assertSame('ops@globallogistics.com', $data['client']['email']);
        $this->assertSame('+1-800-555-0123', $data['client']['phone']);
        $this->assertSame(10.0, $data['taxRate']);
        $this->assertSame(150.0, $data['discount']);
        $this->assertSame('50% advance on signing, 50% on project delivery.', $data['terms']);

        $this->assertCount(2, $data['items']);
        $this->assertSame(4800.0, $data['items'][0]['total']);
        $this->assertSame(900.0, $data['items'][1]['total']);

        // Number override
        $quote->number('QUO-8899');
        $quote->items([['name' => 'Support', 'price' => 500.0, 'qty' => 1, 'total' => 500.0]]);
        $this->assertSame('QUO-8899', $quote->toDataArray()['quoteNumber']);
        $this->assertCount(1, $quote->toDataArray()['items']);
    }

    public function testDeliveryNoteFluentBuilderAndData(): void
    {
        $dn = DeliveryNote::make('DN-3344')
            ->date('2026-10-08')
            ->orderNumber('ORD-5566')
            ->recipient('Mega Retail Outlet', 'Warehouse 4B, Industrial Area', 'Jane Smith', '+1-555-4321')
            ->carrier('FastFreight Cargo', 'TRK-9002', 'KCA 123Z', 'Samuel Driver')
            ->addItem('Ergonomic Office Chair', 10, 'SKU-CHR-01', 'Wooden Crate', 'Pristine')
            ->addItem('Standing Desk Frame', 5, 'SKU-DSK-02', 'Heavy Carton', 'Good')
            ->instructions('Deliver to loading dock 2. Call receiver 30 mins prior.');

        $this->assertSame('delivery_note', $dn->getTemplateName());

        $data = $dn->toDataArray();
        $this->assertSame('DN-3344', $data['dnNumber']);
        $this->assertSame('2026-10-08', $data['deliveryDate']);
        $this->assertSame('ORD-5566', $data['orderNumber']);
        $this->assertSame('Mega Retail Outlet', $data['recipient']['name']);
        $this->assertSame('Warehouse 4B, Industrial Area', $data['recipient']['address']);
        $this->assertSame('Jane Smith', $data['recipient']['contact']);
        $this->assertSame('+1-555-4321', $data['recipient']['phone']);

        $this->assertSame('FastFreight Cargo', $data['carrier']['name']);
        $this->assertSame('TRK-9002', $data['carrier']['tracking_number']);
        $this->assertSame('KCA 123Z', $data['carrier']['vehicle_no']);
        $this->assertSame('Samuel Driver', $data['carrier']['driver']);

        $this->assertCount(2, $data['items']);
        $this->assertSame('SKU-CHR-01', $data['items'][0]['sku']);
        $this->assertSame(10, $data['items'][0]['qty']);
        $this->assertSame('Wooden Crate', $data['items'][0]['package_type']);
        $this->assertSame('Pristine', $data['items'][0]['condition']);
        $this->assertSame('Deliver to loading dock 2. Call receiver 30 mins prior.', $data['deliveryInstructions']);

        $dn->number('DN-9999');
        $dn->items([['name' => 'Single Item', 'qty' => 1, 'sku' => 'SKU-1', 'package_type' => 'Box', 'condition' => 'Good']]);
        $this->assertSame('DN-9999', $dn->toDataArray()['dnNumber']);
        $this->assertCount(1, $dn->toDataArray()['items']);
    }

    public function testPurchaseOrderFluentBuilderAndData(): void
    {
        $po = PurchaseOrder::make('PO-7711')
            ->date('2026-10-10')
            ->expectedDate('2026-10-25')
            ->vendor('Chipset Manufacturer Inc', 'Alice Vendor', '100 Silicon Way', 'sales@chipset.com', '+1-555-9090')
            ->shipTo('Hardware Assembly Facility', '77 Industrial Park', 'Bob Factory', '+1-555-8080')
            ->addItem('Microcontroller Unit MCU-32', 4.50, 1000, 'MCU-32-SMD')
            ->addItem('Power Management IC PMIC-05', 1.20, 2000, 'PMIC-05-SOIC')
            ->taxRate(8.5)
            ->shipping(120.00)
            ->paymentTerms('Net 30 Days')
            ->shippingMethod('Air Express Courier')
            ->deliveryTerms('FOB Destination');

        $this->assertSame('purchase_order', $po->getTemplateName());

        $data = $po->toDataArray();
        $this->assertSame('PO-7711', $data['poNumber']);
        $this->assertSame('2026-10-10', $data['poDate']);
        $this->assertSame('2026-10-25', $data['expectedDate']);
        $this->assertSame('Chipset Manufacturer Inc', $data['vendor']['name']);
        $this->assertSame('Hardware Assembly Facility', $data['shipTo']['name']);
        $this->assertSame(8.5, $data['taxRate']);
        $this->assertSame(120.00, $data['shipping']);
        $this->assertSame('Net 30 Days', $data['paymentTerms']);
        $this->assertSame('Air Express Courier', $data['shippingMethod']);
        $this->assertSame('FOB Destination', $data['deliveryTerms']);

        $this->assertCount(2, $data['items']);
        $this->assertSame(4500.0, $data['items'][0]['total']);
        $this->assertSame(2400.0, $data['items'][1]['total']);

        $po->number('PO-8800');
        $po->items([['name' => 'Screws', 'price' => 0.05, 'qty' => 500, 'sku' => 'SC-01', 'total' => 25.0]]);
        $this->assertSame('PO-8800', $po->toDataArray()['poNumber']);
        $this->assertCount(1, $po->toDataArray()['items']);
    }

    public function testPayslipFluentBuilderAndData(): void
    {
        $payslip = Payslip::make('September 2026')
            ->payDate('2026-09-30')
            ->employee('Sarah Connor', 'EMP-0042', 'Chief Architect', 'Engineering', 'ACC-9988-7766', 'KRA-TAX-123')
            ->addEarning('Basic Salary', 6500.00)
            ->addEarning('Housing Allowance', 1200.00)
            ->addEarning('Performance Bonus', 800.00)
            ->addDeduction('Income Tax / PAYE', 1800.00)
            ->addDeduction('Health Insurance / NHIF', 150.00)
            ->addDeduction('Retirement Pension / NSSF', 200.00);

        $this->assertSame('payslip', $payslip->getTemplateName());

        $data = $payslip->toDataArray();
        $this->assertSame('September 2026', $data['payPeriod']);
        $this->assertSame('2026-09-30', $data['payDate']);
        $this->assertSame('Sarah Connor', $data['employee']['name']);
        $this->assertSame('EMP-0042', $data['employee']['id']);
        $this->assertSame('Chief Architect', $data['employee']['designation']);
        $this->assertSame('Engineering', $data['employee']['department']);
        $this->assertSame('ACC-9988-7766', $data['employee']['bank_account']);
        $this->assertSame('KRA-TAX-123', $data['employee']['tax_id']);

        $this->assertCount(3, $data['earnings']);
        $this->assertSame('Basic Salary', $data['earnings'][0]['title']);
        $this->assertSame(6500.00, $data['earnings'][0]['amount']);

        $this->assertCount(3, $data['deductions']);
        $this->assertSame('Income Tax / PAYE', $data['deductions'][0]['title']);
        $this->assertSame(1800.00, $data['deductions'][0]['amount']);

        // Period setter and bulk overrides
        $payslip->period('October 2026');
        $payslip->earnings([['title' => 'Flat Pay', 'amount' => 5000.00]]);
        $payslip->deductions([['title' => 'Tax', 'amount' => 1000.00]]);

        $data2 = $payslip->toDataArray();
        $this->assertSame('October 2026', $data2['payPeriod']);
        $this->assertCount(1, $data2['earnings']);
        $this->assertCount(1, $data2['deductions']);
    }

    public function testCertificateFluentBuilderAndData(): void
    {
        $cert = Certificate::make('Certificate of Completion')
            ->subtitle('Professional Deep Learning Specialization')
            ->recipient('Evelyn Vance')
            ->presentationLine('This is proudly presented to')
            ->achievement('For successfully completing 120 hours of advanced neural network training with distinction.')
            ->number('CERT-DL-2026-9081')
            ->issueDate('2026-10-12')
            ->addSignatory('Dr. Alan Turing', 'Program Director')
            ->addSignatory('Ada Lovelace', 'Lead Instructor');

        $this->assertSame('certificate', $cert->getTemplateName());

        $data = $cert->toDataArray();
        $this->assertSame('Certificate of Completion', $data['title']);
        $this->assertSame('Professional Deep Learning Specialization', $data['subtitle']);
        $this->assertSame('Evelyn Vance', $data['recipientName']);
        $this->assertSame('This is proudly presented to', $data['presentationLine']);
        $this->assertSame('For successfully completing 120 hours of advanced neural network training with distinction.', $data['achievement']);
        $this->assertSame('CERT-DL-2026-9081', $data['certificateNumber']);
        $this->assertSame('2026-10-12', $data['issueDate']);

        $this->assertCount(2, $data['signatories']);
        $this->assertSame('Dr. Alan Turing', $data['signatories'][0]['name']);
        $this->assertSame('Program Director', $data['signatories'][0]['title']);

        // Title and bulk signatories override
        $cert->title('Master Certificate');
        $cert->signatories([['name' => 'Dean', 'title' => 'Dean of Studies']]);
        $this->assertSame('Master Certificate', $cert->toDataArray()['title']);
        $this->assertCount(1, $cert->toDataArray()['signatories']);
    }

    public function testBuildersProxyingPdfInterfaceMethods(): void
    {
        $invoice = Invoice::make('INV-PROXY-01')
            ->format(PaperFormat::LETTER)
            ->orientation(Orientation::LANDSCAPE)
            ->margins(10, 10, 10, 10, 'mm')
            ->header('<p>Header content</p>')
            ->footer('<p>Footer content</p>')
            ->watermark('CONFIDENTIAL', 0.15, '#ff0000', 45)
            ->emulateMedia('screen')
            ->background(true);

        $fake = Pdf::fake();
        $invoice->download('test-proxy-invoice.pdf');

        $fake->assertRendered('invoice');
        $fake->assertDownloaded('test-proxy-invoice.pdf');
        $fake->assertViewData('invoiceNumber', 'INV-PROXY-01');
    }
}
