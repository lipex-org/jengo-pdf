<?php

declare(strict_types=1);

namespace Jengo\Pdf\Support;

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

        // Code 128B start code is index 104
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

        $checkDigit = $checksum % 103;
        $values[] = $checkDigit;
        $values[] = 106; // Stop code

        $bars = '';
        foreach ($values as $val) {
            $pattern = self::$code128Patterns[$val] ?? self::$code128Patterns[0];
            $bars .= $pattern;
        }

        // Convert widths pattern to rectangles
        $rects = '';
        $x = 10; // Left quiet zone
        $totalModules = 0;
        $isBar = true;

        for ($i = 0; $i < strlen($bars); $i++) {
            $w = (int) $bars[$i] * $width;
            if ($isBar) {
                $rects .= sprintf('<rect x="%d" y="0" width="%d" height="%d" fill="%s" />', $x, $w, $height, htmlspecialchars($color));
            }
            $x += $w;
            $totalModules += (int) $bars[$i];
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
}
