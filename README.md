<p align="center">
  <a href="https://lipex-org.github.io/jengophp.com/">
    <img src="https://raw.githubusercontent.com/lipex-org/docs/main/public/logo-full.png" width="220" alt="Jengo Logo">
  </a>
</p>

<h1 align="center">Jengo PDF</h1>

<p align="center">
  <strong>High-performance PDF builder and document generator (Dompdf & Chromium) with pre-built invoice templates, schema reports, and interactive browser preview.</strong>
</p>

<p align="center">
  <a href="https://lipex-org.github.io/jengophp.com/packages/pdf"><strong>Documentation</strong></a> •
  <a href="https://github.com/lipex-org/pdf/blob/main/LICENSE"><strong>License</strong></a> •
  <a href="https://github.com/lipex-org/pdf/issues"><strong>Issues</strong></a>
</p>

---

## Installation

```bash
composer require jengo/pdf
php spark jengo:install pdf
```

## Quick Start

```php
use Jengo\Pdf\Pdf;

// 1. Fluent commercial invoice
return Pdf::invoice('INV-2026-001')
    ->client(['name' => 'Acme Corp', 'email' => 'billing@acme.com'])
    ->addItem('Cloud Architecture Consulting', 1, 3500.00)
    ->tax(16.0)
    ->download('invoice-001.pdf');

// 2. Interactive in-browser preview canvas
return Pdf::invoice('INV-2026-001')->preview();

// 3. Render raw HTML or CI4 view
return Pdf::view('invoices/receipt', $data)->inline();
```

## Documentation

For full guides on Dompdf vs Chromium drivers, 7 pre-built document builders, schema data reports, interactive preview canvas, vector QR/barcodes, and testing doubles, visit https://lipex-org.github.io/jengophp.com/packages/pdf.

## License

Released under the MIT License.
