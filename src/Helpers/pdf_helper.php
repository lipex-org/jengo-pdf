<?php

declare(strict_types=1);

use Jengo\Pdf\Contracts\PdfInterface;
use Jengo\Pdf\Pdf;

if (!function_exists('pdf')) {
    /**
     * Fluent helper to create a PDF document or render a view.
     *
     * @param string|null          $view Optional view name
     * @param array<string, mixed> $data View parameters
     */
    function pdf(?string $view = null, array $data = []): PdfInterface
    {
        if ($view !== null) {
            return Pdf::view($view, $data);
        }

        return Pdf::newDocument();
    }
}
