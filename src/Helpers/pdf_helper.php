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
     * Render a universally compatible QR code (compatible with Dompdf and browser preview).
     */
    function pdf_qr_code(
        string $text,
        int $size = 120,
        string $color = '#000000',
        string $bgColor = '#ffffff',
        int $margin = 2,
        string $format = 'img'
    ): string {
        return QrCode::render($text, $size, $color, $bgColor, $margin, $format);
    }
}

if (!function_exists('pdf_qr_data_uri')) {
    /**
     * Render a Base64 PNG Data URI QR code suitable for <img src="...">.
     */
    function pdf_qr_data_uri(
        string $text,
        int $size = 120,
        string $color = '#000000',
        string $bgColor = '#ffffff',
        int $margin = 2
    ): string {
        return QrCode::pngDataUri($text, $size, $color, $bgColor, $margin);
    }
}

if (!function_exists('pdf_barcode')) {
    /**
     * Render a universally compatible Barcode (Code 128 standard).
     */
    function pdf_barcode(
        string $code,
        int $height = 40,
        int $width = 2,
        string $color = '#000000',
        string $bgColor = '#ffffff',
        bool $showText = false,
        string $format = 'img'
    ): string {
        return Barcode::render($code, $height, $width, $color, $bgColor, $showText, $format);
    }
}

if (!function_exists('pdf_barcode_data_uri')) {
    /**
     * Render a Base64 PNG Data URI Barcode suitable for <img src="...">.
     */
    function pdf_barcode_data_uri(
        string $code,
        int $height = 40,
        int $width = 2,
        string $color = '#000000',
        string $bgColor = '#ffffff',
        bool $showText = false
    ): string {
        return Barcode::pngDataUri($code, $height, $width, $color, $bgColor, $showText);
    }
}
