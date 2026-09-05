# Jengo PDF (`jengo/pdf`)

A modern, multi-driver PDF generation and document reporting package for CodeIgniter 4 and the Jengo ecosystem.

---

## Features

- **Multi-Driver Engine**:
  - **Chromium / Headless Chrome**: Pixel-perfect rendering with modern CSS Grid, Flexbox, Tailwind CSS, JavaScript charts, and WebFonts.
  - **Dompdf**: Pure PHP, zero-binary fallback for lightweight server environments.
- **Fluent & Expressive API**:
  - Render from CodeIgniter 4 Views, raw HTML strings, external URLs, or Schema models.
  - Stream directly to browser (`inline()`), force file download (`download()`), save to disk (`save()`), get raw binary (`output()`), or retrieve Base64 (`base64()`).
- **First-Class `jengo/schema` Reporting**:
  - Turn database queries, hydrated relations, and entity collections into clean, paginated PDF reports with repeating table headers, column formatters, and summary aggregations (`sum`, `avg`, `count`, `min`, `max`).
- **Comprehensive Configuration**:
  - Full control over paper formats (A4, Letter, Legal, Tabloid, etc.), orientations (Portrait, Landscape), custom margins, headers, footers, page numbers, scale, and media emulation (`screen` / `print`).

---

## Installation

```bash
composer require jengo/pdf
```

To publish the default configuration file:

```bash
php spark jengo:install pdf
```

---

## Basic Usage

### 1. Rendering a CodeIgniter 4 View

```php
use Jengo\Pdf\Pdf;

return Pdf::view('invoices/show', ['invoice' => $invoice])
    ->format('A4')
    ->portrait()
    ->margins(top: 15, right: 10, bottom: 15, left: 10, unit: 'mm')
    ->download('invoice-1001.pdf');
```

### 2. Rendering Raw HTML

```php
use Jengo\Pdf\Pdf;

return Pdf::html('<h1>Monthly Executive Summary</h1><p>Financial overview...</p>')
    ->landscape()
    ->inline('summary.pdf');
```

### 3. Using the Global Helper

```php
// Inline view
return pdf('reports/sales', $data)->inline('sales-report.pdf');

// Custom document
return pdf()->html($html)->download('export.pdf');
```

---

## Generating Reports from `jengo/schema`

You can generate professional tabular reports directly from database models or queries:

```php
use App\Models\Order;
use Jengo\Pdf\Pdf;

return Pdf::fromSchema(Order::schema()->with('customer', 'items')->where('status', 'completed'))
    ->title('Completed Sales Invoices')
    ->subtitle('Q3 Financial Overview')
    ->columns([
        'id'            => '# Order ID',
        'customer.name' => 'Customer',
        'created_at'    => ['label' => 'Date', 'format' => 'date:Y-m-d'],
        'total_amount'  => ['label' => 'Total', 'format' => 'currency:USD', 'align' => 'right'],
    ])
    ->aggregate([
        'total_amount' => 'sum',
    ])
    ->theme('modern-blue') // Themes: modern-blue, emerald, crimson, slate, dark
    ->landscape()
    ->download('orders-summary.pdf');
```

---

## Chromium Driver Options

For exact browser fidelity and client-side JavaScript execution (e.g., ApexCharts / Chart.js):

```php
return Pdf::view('analytics/dashboard', $data)
    ->driver('chromium')
    ->emulateMedia('screen')             // Retain screen CSS, dark mode & backgrounds
    ->background(true)                   // Print background graphics & colors
    ->scale(0.85)                        // Zoom scale (0.1 to 2.0)
    ->waitForSelector('#chart-rendered') // Wait for JS chart to render
    ->waitForTimeout(1000)               // Additional time for fonts/animations
    ->pageNumbers('Page {page} of {pages}')
    ->download('analytics.pdf');
```

---

## Delivery Methods

| Method | Return Type | Description |
| :--- | :--- | :--- |
| `inline(?string $filename)` | `ResponseInterface` | Streams PDF to browser tab with `inline` disposition. |
| `download(?string $filename)` | `ResponseInterface` | Triggers browser download with `attachment` disposition. |
| `save(string $destinationPath)` | `string` | Saves binary PDF directly to local disk. |
| `output()` | `string` | Returns raw binary PDF contents. |
| `base64()` | `string` | Returns Base64-encoded PDF string (ideal for JSON APIs). |

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
