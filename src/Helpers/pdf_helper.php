<?php

declare(strict_types=1);

use Jengo\Pdf\Contracts\PdfInterface;
use Jengo\Pdf\Pdf;
use Jengo\Pdf\Support\Barcode;
use Jengo\Pdf\Support\QrCode;

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

if (!function_exists('pdf_qr_code')) {
    /**
     * Render an inline vector SVG QR code.
     */
    function pdf_qr_code(
        string $text,
        int $size = 120,
        string $color = '#000000',
        string $bgColor = 'transparent',
        int $margin = 2
    ): string {
        return QrCode::svg($text, $size, $color, $bgColor, $margin);
    }
}

if (!function_exists('pdf_qr_data_uri')) {
    /**
     * Render a Base64 SVG Data URI QR code suitable for <img src="...">.
     */
    function pdf_qr_data_uri(
        string $text,
        int $size = 120,
        string $color = '#000000',
        string $bgColor = '#ffffff'
    ): string {
        return QrCode::dataUri($text, $size, $color, $bgColor);
    }
}

if (!function_exists('pdf_barcode')) {
    /**
     * Render an inline vector SVG Barcode (Code 128 standard).
     */
    function pdf_barcode(
        string $code,
        int $height = 40,
        int $width = 2,
        string $color = '#000000',
        bool $showText = false
    ): string {
        return Barcode::code128($code, $height, $width, $color, $showText);
    }
}
