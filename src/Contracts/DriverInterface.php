<?php

declare(strict_types=1);

namespace Jengo\Pdf\Contracts;

use Jengo\Pdf\PdfDocument;

interface DriverInterface
{
    /**
     * Render the given document and return the raw binary PDF string.
     */
    public function render(PdfDocument $document): string;

    /**
     * Check if this driver is supported/available in the current environment.
     */
    public function isAvailable(): bool;

    /**
     * Get the driver's unique name (e.g. 'dompdf', 'chromium').
     */
    public function getName(): string;
}
