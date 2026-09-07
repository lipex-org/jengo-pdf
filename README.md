# Jengo PDF (`jengo/pdf`)

[![Latest Version](https://img.shields.io/badge/version-1.2.0-blue.svg?style=flat-square)](https://github.com/jengo-php/pdf)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-777bb4.svg?style=flat-square)](https://php.net)
[![CodeIgniter 4](https://img.shields.io/badge/codeigniter-4.6%2B-ef4444.svg?style=flat-square)](https://codeigniter.com)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg?style=flat-square)](LICENSE)

A high-performance, dual-driver PDF generation, document templating, database schema reporting, and interactive browser preview engine built specifically for **CodeIgniter 4** and the **Jengo Framework**.

---

## 🌟 Key Highlights

- ⚡ **Dual-Driver Architecture**:
  - **Dompdf**: Ultra-fast, pure-PHP, zero-external-binary fallback for lightweight production servers.
  - **Headless Chromium**: Full browser engine executing modern CSS Grid, Flexbox, Tailwind CSS, WebFonts, and JavaScript charts (ApexCharts, Chart.js).
- 🏗️ **Type-Safe Fluent Document Builders**:
  - 7 battle-tested, out-of-the-box business templates: **Commercial Invoices**, **Quotations & Proposals**, **Payment Receipts**, **Delivery Dispatch Notes**, **Confidential Payslips**, **Procurement Purchase Orders**, and **Certificates of Excellence**.
- 📊 **Declarative Schema Reporting (`jengo/schema`)**:
  - Stream database queries, models, or collections into multi-page tabular reports with automatic column formatting (currencies, dates, badges, sums) and 7 preset themes + custom palette builder (`ReportTheme`).
- 👁️ **Interactive In-Browser Live Preview Canvas**:
  - Full-screen sheet simulator with dark-mode toolbar, live zoom, fit-to-width, paginated vs. continuous scroll toggles, automatic sheet pagination, and direct print/download shortcuts.
- 📱 **Zero-Dependency Vector QR & Barcodes**:
  - Universal pure-PHP vector SVG and Base64 PNG QR codes and Code 128 barcodes compatible with Dompdf and browser previews.
- 💱 **Universal Currency Engine**:
  - Intelligent monetary amount formatting supporting standard currency symbols (`$`, `€`, `£`) and ISO codes (`KES`, `USD`, `EUR`, `Ksh`) with correct typography, spacing rules, and parsing.
- 💧 **First-Class Watermarking**:
  - Angled, centered, multi-page repeating watermarks defaulting to `'JENGO'` with customizable text, opacity, color, angle, and size.
- 🧪 **Testing Double & Assertions**:
  - Built-in `Pdf::fake()` with expressive assertion methods for unit and feature testing without generating real PDF binaries.

---

## 📦 Installation

Install via Composer:

```bash
composer require jengo/pdf
```

Publish the default configuration to `app/Config/Pdf.php`:

```bash
php spark jengo:install pdf
```

---

## 🚀 Quick Start

### 1. Type-Safe Fluent Document Builders

Generate professional documents with full IDE auto-completion and zero magic arrays:

#### Commercial Invoice
```php
use Jengo\Pdf\Pdf;

return Pdf::invoice('INV-2026-001')
    ->customer('Wayne Enterprises', address: '1007 Mountain Dr, Gotham', email: 'bruce@wayne.com')
    ->addItem('Advanced Cybernetic Defense Grid', 75000.00, qty: 1)
    ->addItem('Encrypted Satellite Uplink Module', 12500.00, qty: 2)
    ->taxRate(0.12)
    ->discount(5000.00)
    ->paymentTerms('Net 30 Days')
    ->bankDetails('Gotham City Bank | IBAN: US89 3704 0044 | SWIFT: GCBKUS33')
    ->watermark('PAID', opacity: 0.12, color: '#16a34a')
    ->paid()
    ->download('invoice-INV-2026-001.pdf');
```

#### Detailed Quotation / Proposal
```php
return Pdf::quotation('QUO-2026-042')
    ->client('Starlight Fintech', contact: 'Marcus Sterling', email: 'marcus@starlight.io')
    ->project('Core Payment Gateway & Realtime Reporting Migration')
    ->addItem('Phase 1: Architecture Blueprint & Security Audit', 3000.00, qty: 40, unit: 'hrs')
    ->addItem('Phase 2: PDF Pipeline & Asynchronous Workers', 4500.00, qty: 60, unit: 'hrs')
    ->validUntil('Oct 21, 2026')
    ->taxRate(0.08)
    ->discount(500.00)
    ->preview();
```

#### Payment Receipt Voucher
```php
return Pdf::receipt('REC-2026-9041')
    ->payer('Nexus Innovations Ltd', taxId: 'TAX-NX-88190')
    ->amount(3500.00, inWords: 'Three Thousand Five Hundred US Dollars Only')
    ->paymentMethod('M-Pesa Express / Wire Transfer')
    ->transactionRef('TXN-984128540')
    ->invoiceRef('INV-2026-089')
    ->addItem('Q3 Enterprise Cloud Infrastructure', 2500.00)
    ->addItem('PDF Reporting Engine License', 1000.00)
    ->download('receipt.pdf');
```

#### Employee Payslip
```php
return Pdf::payslip('August 2026')
    ->employee('Alex Morgan', id: 'EMP-0842', designation: 'Principal Architect', department: 'Infrastructure')
    ->addEarning('Basic Salary', 5800.00)
    ->addEarning('House & Accommodation Allowance', 1400.00)
    ->addEarning('Performance Bonus', 850.00)
    ->addDeduction('PAYE (Income Tax)', 1890.00)
    ->addDeduction('Social Health Insurance (SHIF)', 235.00)
    ->addDeduction('Staff Provident Fund', 300.00)
    ->download('payslip.pdf');
```

#### Certificate of Excellence
```php
return Pdf::certificate('Certificate of Excellence')
    ->recipient('Ada Lovelace')
    ->achievement('For Pioneering Computer Science & Algorithmic Architecture')
    ->number('JENGO-CERT-2026-001')
    ->addSignatory('Charles Babbage', 'Director of Engineering')
    ->addSignatory('Alan Turing', 'Dean of Research')
    ->download('certificate.pdf');
```

---

### 2. Database Schema Reporting (`jengo/schema`)

Generate multi-page formatted reports directly from CI4 Models, database queries, or collection arrays:

```php
use App\Models\OrderModel;
use Jengo\Pdf\Pdf;
use Jengo\Pdf\Schema\Column;

return Pdf::fromSchema(model(OrderModel::class)->where('status', 'completed'))
    ->title('Executive Sales & Operations Report')
    ->subtitle('Fiscal Performance Analysis')
    ->theme('modern-blue') // Presets: modern-blue, emerald, crimson, amber, indigo, slate, minimal-dark
    ->columns([
        Column::make('id', 'Order ID')->width('14%')->align('center'),
        Column::make('client', 'Customer Name')->width('24%'),
        Column::make('created_at', 'Transaction Date')->date('M d, Y')->width('16%'),
        Column::make('status', 'Status')->badge([
            'completed' => 'success',
            'pending'   => 'warning',
            'refunded'  => 'danger',
        ])->align('center')->width('14%'),
        Column::make('amount', 'Revenue')->currency('KES')->sum()->align('right')->width('18%'),
    ])
    ->watermark('CONFIDENTIAL')
    ->preview();
```

#### Bespoke Theme Builder
```php
use Jengo\Pdf\Schema\ReportTheme;

$theme = ReportTheme::make()
    ->primary('#7c3aed')      // Header & brand background
    ->secondary('#5b21b6')    // Titles and section highlights
    ->headerText('#ffffff')   // Header text color
    ->zebra('#f5f3ff')        // Alternating row background
    ->border('#ddd6fe')       // Table dividers
    ->font('DejaVu Sans, Helvetica, sans-serif')
    ->footerText('#8b5cf6')
    ->customCss('.title { letter-spacing: 1px; }');

return Pdf::fromSchema($query)->theme($theme)->download('custom-report.pdf');
```

---

### 3. Rendering Views and Raw HTML

```php
// Render a standard CodeIgniter 4 View template
return Pdf::view('invoices/show', ['invoice' => $invoice])
    ->format('A4')
    ->portrait()
    ->margins(top: 10, right: 10, bottom: 10, left: 10, unit: 'mm')
    ->inline('invoice.pdf');

// Render a dynamic HTML string
return Pdf::html('<h1>Direct HTML Output</h1><p>Compiled on the fly.</p>')
    ->watermark('DRAFT')
    ->download('document.pdf');

// Render an external web page
return Pdf::url('https://example.com/invoice/preview')
    ->driver('chromium')
    ->download('webpage.pdf');
```

---

### 4. Interactive Browser Preview Canvas

Provide users with an interactive, rich document preview interface with sheet frames, dynamic pagination, zoom, and print controls:

```php
public function previewInvoice(): ResponseInterface
{
    return Pdf::invoice('INV-2026-089')
        ->customer('Sarah Jenkins')
        ->addItem('Architecture Consulting', 2500.00)
        ->preview(); // Renders responsive viewer toolbar and paginated sheets
}
```

**Viewer Features**:
- 🔍 **Zoom**: `+` (Zoom In), `-` (Zoom Out), `0` (Reset 100%), `Fit Width` toggle.
- 📜 **View Modes**: Toggle between **Paginated Sheets** (exact millimeter A4/Letter pages) and **Continuous Scroll**.
- ⌨️ **Keyboard Shortcuts**: `PageUp` / `PageDown` / `ArrowUp` / `ArrowDown` for page navigation, `D` to download PDF.
- 🖨️ **Direct Print**: `Print` button formatted cleanly via CSS print media styles.

---

### 5. Pure-PHP Vector QR & Barcodes

Embed high-density QR codes and Code 128 barcodes into your templates with zero external dependencies:

```php
// Render Vector SVG
<?= pdf_qr_code('https://jengo.dev/verify/INV-001', size: 100) ?>
<?= pdf_barcode('INV-2026-001', height: 35, showText: true) ?>

// Render Base64 PNG Data URI (for standard <img src="..."> tags)
<img src="<?= pdf_qr_data_uri('https://jengo.dev/verify/INV-001', size: 100) ?>" />
<img src="<?= pdf_barcode_data_uri('INV-2026-001', height: 35) ?>" />
```

---

### 6. Watermarks

Add watermarks across any document or template. Watermarks default to `'JENGO'` with subtle opacity (`0.08`), angled at `-35°`, centered, and repeated across every page:

```php
// Via Facade
Pdf::watermark('JENGO')->html($html)->preview();

// Via Document Builder
Pdf::invoice()->watermark('CONFIDENTIAL', opacity: 0.15, color: '#dc2626')->preview();

// In custom views via helper
<?= pdf_watermark('PAID', opacity: 0.12, color: '#16a34a') ?>
```

---

### 7. Global Helpers

| Helper | Signature | Description |
| :--- | :--- | :--- |
| `pdf()` | `pdf(?string $view, array $data): PdfInterface` | Starts a new PDF document or loads a view template. |
| `pdf_qr_code()` | `pdf_qr_code(string $text, int $size = 120, ...): string` | Generates a vector SVG QR code. |
| `pdf_qr_data_uri()` | `pdf_qr_data_uri(string $text, int $size = 120, ...): string` | Generates a Base64 PNG Data URI for QR codes. |
| `pdf_barcode()` | `pdf_barcode(string $code, int $height = 40, ...): string` | Generates a Code 128 vector barcode. |
| `pdf_barcode_data_uri()` | `pdf_barcode_data_uri(string $code, ...): string` | Generates a Base64 PNG Data URI for barcodes. |
| `pdf_currency()` | `pdf_currency(mixed $amount, ?string $currency = null, ...): string` | Formats currency with consistent spacing (e.g. `KES 1,200.00`, `$1,200.00`). |
| `pdf_watermark()` | `pdf_watermark(string\|bool\|array $textOrConfig, ...): string` | Injects watermark CSS and HTML into custom view templates. |

---

### 8. Delivery & Storage Methods

| Method | Return Type | Description |
| :--- | :--- | :--- |
| `inline(?string $filename)` | `ResponseInterface` | Streams PDF to the browser tab (`Content-Disposition: inline`). |
| `download(?string $filename)` | `ResponseInterface` | Triggers a browser file download (`Content-Disposition: attachment`). |
| `preview(bool $withToolbar = true)` | `ResponseInterface` | Displays the interactive in-browser preview canvas with toolbar. |
| `save(string $destinationPath)` | `string` | Saves the PDF binary directly to local disk. |
| `store(string $path, ?string $disk = null)` | `string` | Stores the PDF to `jengo/storage` disk or CI4 `WRITEPATH`. |
| `attachTo(mixed $email, ?string $filename = null)` | `static` | Attaches the PDF directly to a CodeIgniter `Email` instance. |
| `output()` | `string` | Returns raw binary PDF contents. |
| `base64()` | `string` | Returns Base64-encoded PDF string (ideal for REST JSON APIs). |
| `dataUri()` | `string` | Returns `data:application/pdf;base64,...` data URI. |

---

## 🧪 Testing with `Pdf::fake()`

Test your PDF generation endpoints and assertions without compiling actual binary files:

```php
use Jengo\Pdf\Pdf;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    public function testInvoiceDownload(): void
    {
        Pdf::fake();

        $this->get('/invoices/INV-001/download');

        // Assertions
        Pdf::assertDownloaded('invoice-INV-001.pdf');
        Pdf::assertViewData('invoiceNumber', 'INV-001');
        Pdf::assertSee('Wayne Enterprises');
        Pdf::assertCount(1);
    }

    public function testNothingRenderedOnUnauthorized(): void
    {
        Pdf::fake();

        $this->get('/invoices/secret/download');

        Pdf::assertNothingRendered();
    }
}
```

**Available Assertions**:
- `Pdf::assertRendered(string|callable $viewOrCallback)`
- `Pdf::assertNotRendered(string|callable $viewOrCallback)`
- `Pdf::assertDownloaded(?string $filename = null, ?callable $callback = null)`
- `Pdf::assertNotDownloaded(?string $filename = null)`
- `Pdf::assertInline(?string $filename = null, ?callable $callback = null)`
- `Pdf::assertNotInline(?string $filename = null)`
- `Pdf::assertSaved(string|callable|null $destinationOrCallback = null)`
- `Pdf::assertStored(string|callable|null $destinationOrCallback = null)`
- `Pdf::assertViewData(string $key, mixed $expectedValue = null, ?string $template = null)`
- `Pdf::assertSee(string $needle, ?string $template = null)`
- `Pdf::assertCount(int $expectedCount)`
- `Pdf::assertNothingRendered()`

---

## ⚙️ Configuration

Custom default settings can be modified in `app/Config/Pdf.php`:

```php
namespace Config;

use Jengo\Pdf\Config\Pdf as BasePdf;
use Jengo\Pdf\Enums\Orientation;
use Jengo\Pdf\Enums\PaperFormat;

class Pdf extends BasePdf
{
    public string $driver = 'dompdf'; // 'dompdf' or 'chromium'

    public PaperFormat $defaultFormat = PaperFormat::A4;
    public Orientation $defaultOrientation = Orientation::PORTRAIT;

    public array $defaultMargins = [
        'top'    => 10.0,
        'right'  => 10.0,
        'bottom' => 10.0,
        'left'   => 10.0,
        'unit'   => 'mm',
    ];

    public array $templating = [
        'brand' => [
            'name'        => 'Jengo Cloud Solutions Ltd',
            'tagline'     => 'Next-Generation Web & Cloud Architecture',
            'email'       => 'support@jengo.dev',
            'phone'       => '+254 700 000 000',
            'footer_text' => 'Generated electronically • Valid without physical signature.',
        ],
        'defaults' => [
            'currency'    => 'KES',
            'date_format' => 'M d, Y',
            'watermark'   => [
                'enabled' => false,
                'text'    => 'JENGO',
                'opacity' => 0.08,
                'color'   => '#64748b',
                'size'    => '64pt',
                'angle'   => -35,
            ],
        ],
    ];
}
```

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
