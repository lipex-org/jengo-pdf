<?php

declare(strict_types=1);

namespace Jengo\Pdf\Support;

/**
 * Pure-PHP Code 128-B Barcode Generator.
 *
 * Generates standards-compliant vector SVG, raster PNG, and base64 Data URIs
 * for 100% universal compatibility across Dompdf, Chromium, and browser preview.
 */
class Barcode
{
    /**
     * Code 128 Barcode patterns for ASCII chars 0-105.
     *
     * @var array<int, string>
     */
    protected static array $code128Patterns = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112'
    ];

    /**
     * Generate an <img> tag with Base64 PNG data for universal Dompdf and browser compatibility.
     */
    public static function img(
        string $code,
        int $height = 40,
        int $width = 2,
        string $color = '#000000',
        string $bgColor = '#ffffff',
        bool $showText = false,
        string $extraStyle = '',
        string $alt = 'Barcode'
    ): string {
        $uri = self::pngDataUri($code, $height, $width, $color, $bgColor, $showText);
        $totalHeight = $showText ? $height + 14 : $height;
        return sprintf(
            '<img src="%s" height="%d" alt="%s" style="display:inline-block; vertical-align:middle; %s" />',
            $uri,
            $totalHeight,
            htmlspecialchars($alt),
            $extraStyle
        );
    }

    /**
     * Render Barcode as either <img>, SVG, or Data URI.
     */
    public static function render(
        string $code,
        int $height = 40,
        int $width = 2,
        string $color = '#000000',
        string $bgColor = '#ffffff',
        bool $showText = false,
        string $format = 'img'
    ): string {
        return match (strtolower($format)) {
            'svg'      => self::code128($code, $height, $width, $color, $showText),
            'data-uri' => self::pngDataUri($code, $height, $width, $color, $bgColor, $showText),
            'svg-uri'  => self::dataUri($code, $height, $width, $color, $bgColor, $showText),
            'png'      => self::png($code, $height, $width, $color, $bgColor, $showText),
            default    => self::img($code, $height, $width, $color, $bgColor, $showText),
        };
    }

    /**
     * Generate a binary PNG image for the Code 128 barcode (pure PHP, zero dependencies).
     */
    public static function png(
        string $code,
        int $height = 40,
        int $moduleWidth = 2,
        string $color = '#000000',
        string $bgColor = '#ffffff',
        bool $showText = false
    ): string {
        $bars = self::calculateBars($code);
        $quietModules = 10;
        $totalModules = count($bars) + ($quietModules * 2);

        $scale = max(3, $moduleWidth * 2);
        $imgW = $totalModules * $scale;
        $imgH = max(60, $height * 2);

        $fg = self::hexToRgb($color);
        $bg = ($bgColor === 'transparent' || $bgColor === '') ? [255, 255, 255] : self::hexToRgb($bgColor);

        $raw = '';
        for ($y = 0; $y < $imgH; $y++) {
            $raw .= "\x00"; // Filter byte 0
            for ($x = 0; $x < $imgW; $x++) {
                $modIdx = (int) floor($x / $scale) - $quietModules;
                $isDark = ($modIdx >= 0 && $modIdx < count($bars) && $bars[$modIdx] === 1);
                $pixelColor = $isDark ? $fg : $bg;
                $raw .= chr($pixelColor[0]) . chr($pixelColor[1]) . chr($pixelColor[2]);
            }
        }

        $compressed = gzcompress($raw, 6);
        $ihdrData = pack('NNCCCCC', $imgW, $imgH, 8, 2, 0, 0, 0);
        $ihdr = 'IHDR' . $ihdrData . pack('N', crc32('IHDR' . $ihdrData));
        $idat = 'IDAT' . $compressed . pack('N', crc32('IDAT' . $compressed));
        $iend = 'IEND' . pack('N', crc32('IEND'));

        return "\x89PNG\r\n\x1a\n"
            . pack('N', strlen($ihdrData)) . $ihdr
            . pack('N', strlen($compressed)) . $idat
            . pack('N', 0) . $iend;
    }

    /**
     * Generate a Base64 PNG Data URI for universal Dompdf and browser rendering.
     */
    public static function pngDataUri(
        string $code,
        int $height = 40,
        int $width = 2,
        string $color = '#000000',
        string $bgColor = '#ffffff',
        bool $showText = false
    ): string {
        $png = self::png($code, $height, $width, $color, $bgColor, $showText);
        return 'data:image/png;base64,' . base64_encode($png);
    }

    /**
     * Generate SVG barcode (Code 128-B standard).
     */
    public static function code128(
        string $code,
        int $height = 40,
        int $width = 2,
        string $color = '#000000',
        bool $showText = false
    ): string {
        $code = trim($code);
        if ($code === '') {
            $code = 'SAMPLE';
        }

        $values = [104]; // Code 128B Start Code
        $checksum = 104;

        $length = strlen($code);
        for ($i = 0; $i < $length; $i++) {
            $ascii = ord($code[$i]);
            $val = $ascii - 32;
            if ($val < 0 || $val > 95) {
                $val = 0;
            }
            $values[] = $val;
            $checksum += $val * ($i + 1);
        }

        $checkDigit = $checksum % 103;
        $values[] = $checkDigit;
        $values[] = 106; // Stop code

        $bars = '';
        foreach ($values as $val) {
            $pattern = self::$code128Patterns[$val] ?? self::$code128Patterns[0];
            $bars .= $pattern;
        }

        $rects = '';
        $x = 10; // Left quiet zone
        $isBar = true;

        for ($i = 0; $i < strlen($bars); $i++) {
            $w = (int) $bars[$i] * $width;
            if ($isBar) {
                $rects .= sprintf('<rect x="%d" y="0" width="%d" height="%d" fill="%s" />', $x, $w, $height, htmlspecialchars($color));
            }
            $x += $w;
            $isBar = !$isBar;
        }

        $totalWidth = $x + 10;
        $totalHeight = $showText ? $height + 14 : $height;
        $textSvg = '';

        if ($showText) {
            $textX = (int) ($totalWidth / 2);
            $textY = $height + 11;
            $textSvg = sprintf(
                '<text x="%d" y="%d" text-anchor="middle" font-family="monospace" font-size="10" fill="%s">%s</text>',
                $textX,
                $textY,
                htmlspecialchars($color),
                htmlspecialchars($code)
            );
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" style="display:inline-block; vertical-align:middle;">%s%s</svg>',
            $totalWidth,
            $totalHeight,
            $totalWidth,
            $totalHeight,
            $rects,
            $textSvg
        );
    }

    /**
     * Alias for code128 SVG generator.
     */
    public static function svg(
        string $code,
        int $height = 40,
        int $width = 2,
        string $color = '#000000',
        bool $showText = false
    ): string {
        return self::code128($code, $height, $width, $color, $showText);
    }

    /**
     * Generate Base64 Data URI for Barcode (defaults to PNG for Dompdf compatibility).
     */
    public static function dataUri(
        string $code,
        int $height = 40,
        int $width = 2,
        string $color = '#000000',
        string $bgColor = '#ffffff',
        bool $showText = false
    ): string {
        return self::pngDataUri($code, $height, $width, $color, $bgColor, $showText);
    }

    /**
     * Calculate 1D binary bar sequence (1=black, 0=white) for Code 128.
     *
     * @return array<int, int>
     */
    protected static function calculateBars(string $code): array
    {
        $code = trim($code);
        if ($code === '') {
            $code = 'SAMPLE';
        }

        $values = [104];
        $checksum = 104;

        $length = strlen($code);
        for ($i = 0; $i < $length; $i++) {
            $ascii = ord($code[$i]);
            $val = $ascii - 32;
            if ($val < 0 || $val > 95) {
                $val = 0;
            }
            $values[] = $val;
            $checksum += $val * ($i + 1);
        }

        $values[] = $checksum % 103;
        $values[] = 106;

        $patternStr = '';
        foreach ($values as $val) {
            $patternStr .= self::$code128Patterns[$val] ?? self::$code128Patterns[0];
        }

        $bars = [];
        $isBar = true;
        for ($i = 0; $i < strlen($patternStr); $i++) {
            $w = (int) $patternStr[$i];
            for ($k = 0; $k < $w; $k++) {
                $bars[] = $isBar ? 1 : 0;
            }
            $isBar = !$isBar;
        }

        return $bars;
    }

    protected static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            return [
                (int) hexdec($hex[0] . $hex[0]),
                (int) hexdec($hex[1] . $hex[1]),
                (int) hexdec($hex[2] . $hex[2]),
            ];
        }
        if (strlen($hex) >= 6) {
            return [
                (int) hexdec(substr($hex, 0, 2)),
                (int) hexdec(substr($hex, 2, 2)),
                (int) hexdec(substr($hex, 4, 2)),
            ];
        }
        return [0, 0, 0];
    }
}
