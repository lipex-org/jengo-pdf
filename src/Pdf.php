<?php

declare(strict_types=1);

namespace Jengo\Pdf;

use Jengo\Pdf\Contracts\DriverInterface;
use Jengo\Pdf\Contracts\PdfInterface;
use Jengo\Pdf\Contracts\SchemaReportInterface;
use Jengo\Pdf\Documents\Certificate;
use Jengo\Pdf\Documents\DeliveryNote;
use Jengo\Pdf\Documents\Invoice;
use Jengo\Pdf\Documents\Payslip;
use Jengo\Pdf\Documents\PurchaseOrder;
use Jengo\Pdf\Documents\Quotation;
use Jengo\Pdf\Documents\Receipt;
use Jengo\Pdf\Schema\ReportTheme;
use Jengo\Pdf\Schema\SchemaReportBuilder;
use Jengo\Pdf\Testing\PdfFake;

class Pdf
{
    protected static ?PdfFake $fakeInstance = null;

    /**
     * Activate in-memory PDF testing fake double.
     */
    public static function fake(): PdfFake
    {
        static::$fakeInstance = new PdfFake();
        return static::$fakeInstance;
    }

    /**
     * Check if PDF testing fake mode is currently active.
     */
    public static function isFaking(): bool
    {
        return static::$fakeInstance !== null;
    }

    /**
     * Get the active testing fake instance, if any.
     */
    public static function getFake(): ?PdfFake
    {
        return static::$fakeInstance;
    }

    /**
     * Reset and deactivate PDF testing fake mode.
     */
    public static function reset(): void
    {
        static::$fakeInstance = null;
    }

    // Testing Assertions

    public static function assertRendered(string|callable $viewOrCallback): void
    {
        static::requireFake()->assertRendered($viewOrCallback);
    }

    public static function assertNotRendered(string|callable $viewOrCallback): void
    {
        static::requireFake()->assertNotRendered($viewOrCallback);
    }

    public static function assertDownloaded(?string $filename = null, ?callable $callback = null): void
    {
        static::requireFake()->assertDownloaded($filename, $callback);
    }

    public static function assertNotDownloaded(?string $filename = null): void
    {
        static::requireFake()->assertNotDownloaded($filename);
    }

    public static function assertInline(?string $filename = null, ?callable $callback = null): void
    {
        static::requireFake()->assertInline($filename, $callback);
    }

    public static function assertNotInline(?string $filename = null): void
    {
        static::requireFake()->assertNotInline($filename);
    }

    public static function assertSaved(string|callable|null $destinationOrCallback = null): void
    {
        static::requireFake()->assertSaved($destinationOrCallback);
    }

    public static function assertStored(string|callable|null $destinationOrCallback = null): void
    {
        static::requireFake()->assertStored($destinationOrCallback);
    }

    public static function assertCount(int $expectedCount): void
    {
        static::requireFake()->assertCount($expectedCount);
    }

    public static function assertNothingRendered(): void
    {
        static::requireFake()->assertNothingRendered();
    }

    public static function assertViewData(string $key, mixed $expectedValue = null, ?string $template = null): void
    {
        static::requireFake()->assertViewData($key, $expectedValue, $template);
    }

    public static function assertSee(string $needle, ?string $template = null): void
    {
        static::requireFake()->assertSee($needle, $template);
    }

    protected static function requireFake(): PdfFake
    {
        if (static::$fakeInstance === null) {
            throw new \RuntimeException('Cannot make assertions on Pdf before calling Pdf::fake().');
        }

        return static::$fakeInstance;
    }

    // Fluent Document Builders

    public static function invoice(string $number = 'INV-001'): Invoice
    {
        return Invoice::make($number);
    }

    public static function quotation(string $number = 'QUO-001'): Quotation
    {
        return Quotation::make($number);
    }

    public static function receipt(string $number = 'REC-001'): Receipt
    {
        return Receipt::make($number);
    }

    public static function deliveryNote(string $number = 'DN-001'): DeliveryNote
    {
        return DeliveryNote::make($number);
    }

    public static function payslip(?string $period = null): Payslip
    {
        return Payslip::make($period);
    }

    public static function purchaseOrder(string $number = 'PO-001'): PurchaseOrder
    {
        return PurchaseOrder::make($number);
    }

    public static function certificate(string $title = 'Certificate of Excellence'): Certificate
    {
        return Certificate::make($title);
    }

    // Standard Document Generation

    /**
     * Create a new PDF document from raw HTML string.
     */
    public static function html(string $html): PdfInterface
    {
        return static::newDocument()->html($html);
    }

    /**
     * Create a new PDF document from a CodeIgniter 4 View template.
     */
    public static function view(string $view, array $data = []): PdfInterface
    {
        return static::newDocument()->view($view, $data);
    }

    /**
     * Create a new PDF document from a built-in standard template (or app override).
     */
    public static function template(string $name, array $data = []): PdfInterface
    {
        return static::newDocument()->template($name, $data);
    }

    /**
     * Register a custom named schema report theme globally.
     */
    public static function registerTheme(string $name, array|ReportTheme $theme): void
    {
        ReportTheme::register($name, $theme);
    }

    /**
     * Create a new PDF document from a URL.
     */
    public static function url(string $url): PdfInterface
    {
        return static::newDocument()->url($url);
    }

    /**
     * Create a new PDF document using a specific driver.
     */
    public static function driver(string|DriverInterface $driver): PdfInterface
    {
        return static::newDocument()->driver($driver);
    }

    /**
     * Create a new report builder from a jengo/schema query, model, or entity collection.
     */
    public static function fromSchema(mixed $schemaOrQuery): SchemaReportInterface
    {
        return new SchemaReportBuilder($schemaOrQuery);
    }

    /**
     * Create a new PDF document with a configured watermark.
     */
    public static function watermark(
        string|bool|array $textOrConfig = 'JENGO',
        float $opacity = 0.08,
        ?string $color = null,
        ?int $angle = -35,
        ?string $size = null
    ): PdfInterface {
        return static::newDocument()->watermark($textOrConfig, $opacity, $color, $angle, $size);
    }

    /**
     * Create a new clean PdfDocument instance.
     */
    public static function newDocument(): PdfDocument
    {
        return new PdfDocument();
    }
}
