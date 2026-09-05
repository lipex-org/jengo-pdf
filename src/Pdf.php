<?php

declare(strict_types=1);

namespace Jengo\Pdf;

use Jengo\Pdf\Contracts\DriverInterface;
use Jengo\Pdf\Contracts\PdfInterface;
use Jengo\Pdf\Contracts\SchemaReportInterface;
use Jengo\Pdf\Schema\SchemaReportBuilder;

class Pdf
{
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
     * Create a new clean PdfDocument instance.
     */
    public static function newDocument(): PdfDocument
    {
        return new PdfDocument();
    }
}
