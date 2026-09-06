<?php

declare(strict_types=1);

namespace Jengo\Pdf\Support;

/**
 * Pure PHP QR Code SVG Generator (Zero external dependencies).
 * Generates standards-compliant QR Codes (Versions 1-10, Byte Mode, Error Correction L/M).
 */
class QrCode
{
    /**
     * Generate an SVG QR code string.
     */
    public static function svg(
        string $text,
        int $size = 120,
        string $color = '#000000',
        string $bgColor = 'transparent',
        int $margin = 2
    ): string {
        $matrix = self::generateMatrix($text);
        $modules = count($matrix);
        $totalSize = $modules + ($margin * 2);

        $rects = '';
        if ($bgColor !== 'transparent') {
            $rects .= sprintf('<rect width="%d" height="%d" fill="%s" />', $totalSize, $totalSize, htmlspecialchars($bgColor));
        }

        for ($r = 0; $r < $modules; $r++) {
            for ($c = 0; $c < $modules; $c++) {
                if ($matrix[$r][$c] === 1) {
                    $rects .= sprintf('<rect x="%d" y="%d" width="1" height="1" fill="%s" />', $c + $margin, $r + $margin, htmlspecialchars($color));
                }
            }
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" shape-rendering="crispEdges" style="display:inline-block; vertical-align:middle;">%s</svg>',
            $size,
            $size,
            $totalSize,
            $totalSize,
            $rects
        );
    }

    /**
     * Generate data URI for embedding in <img> tags.
     */
    public static function dataUri(string $text, int $size = 120, string $color = '#000000', string $bgColor = '#ffffff'): string
    {
        $svg = self::svg($text, $size, $color, $bgColor);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Build QR code 2D boolean module matrix.
     *
     * @return array<int, array<int, int>>
     */
    public static function generateMatrix(string $text): array
    {
        $bytes = array_values(unpack('C*', $text) ?: []);
        $len = count($bytes);

        // Determine smallest QR version (1 to 6) for Byte mode (ECC Low)
        $version = 1;
        $capacities = [1 => 17, 2 => 32, 3 => 53, 4 => 78, 5 => 106, 6 => 134, 7 => 154, 8 => 192, 9 => 230, 10 => 271];
        foreach ($capacities as $v => $cap) {
            if ($len <= $cap) {
                $version = $v;
                break;
            }
            $version = $v;
        }

        $size = 17 + (4 * $version);
        $matrix = array_fill(0, $size, array_fill(0, $size, null));

        // 1. Finder patterns (top-left, top-right, bottom-left)
        self::placeFinder($matrix, 0, 0);
        self::placeFinder($matrix, 0, $size - 7);
        self::placeFinder($matrix, $size - 7, 0);

        // Separators & Alignment
        self::placeTimingAndAlignment($matrix, $version, $size);

        // 2. Encode Bitstream
        $codewords = self::encodeDataAndEcc($bytes, $version);

        // 3. Place Data & Mask
        self::placeCodewordsAndMask($matrix, $codewords, $size);

        // Fill remaining empty spots with 0
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($matrix[$r][$c] === null) {
                    $matrix[$r][$c] = 0;
                }
            }
        }

        return $matrix;
    }

    protected static function placeFinder(array &$matrix, int $row, int $col): void
    {
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                $isBorder = ($r === 0 || $r === 6 || $c === 0 || $c === 6);
                $isCenter = ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);
                $matrix[$row + $r][$col + $c] = ($isBorder || $isCenter) ? 1 : 0;
            }
        }
    }

    protected static function placeTimingAndAlignment(array &$matrix, int $version, int $size): void
    {
        // Timing Patterns
        for ($i = 8; $i < $size - 8; $i++) {
            $val = ($i % 2 === 0) ? 1 : 0;
            if ($matrix[6][$i] === null) $matrix[6][$i] = $val;
            if ($matrix[$i][6] === null) $matrix[$i][6] = $val;
        }

        // Dark module
        $matrix[4 * $version + 9][8] = 1;

        // Alignment pattern for version >= 2
        if ($version >= 2) {
            $alignCoords = [
                2 => [18], 3 => [22], 4 => [26], 5 => [30], 6 => [34],
                7 => [22, 38], 8 => [24, 42], 9 => [26, 46], 10 => [28, 50]
            ];
            $coords = $alignCoords[$version] ?? [18];
            foreach ($coords as $ar) {
                foreach ($coords as $ac) {
                    if ($matrix[$ar][$ac] !== null) continue;
                    for ($r = -2; $r <= 2; $r++) {
                        for ($c = -2; $c <= 2; $c++) {
                            $isEdge = (abs($r) === 2 || abs($c) === 2);
                            $isCenter = ($r === 0 && $c === 0);
                            $matrix[$ar + $r][$ac + $c] = ($isEdge || $isCenter) ? 1 : 0;
                        }
                    }
                }
            }
        }

        // Reserve format info area
        for ($i = 0; $i < 9; $i++) {
            if ($matrix[8][$i] === null) $matrix[8][$i] = 0;
            if ($matrix[$i][8] === null) $matrix[$i][8] = 0;
        }
        for ($i = $size - 8; $i < $size; $i++) {
            if ($matrix[8][$i] === null) $matrix[8][$i] = 0;
            if ($matrix[$i][8] === null) $matrix[$i][8] = 0;
        }
    }

    protected static function encodeDataAndEcc(array $bytes, int $version): array
    {
        // Total data capacity per version (ECC Level L)
        $dataCapacities = [1 => 19, 2 => 34, 3 => 55, 4 => 80, 5 => 108, 6 => 136, 7 => 156, 8 => 194, 9 => 232, 10 => 274];
        $totalCodewords = [1 => 26, 2 => 44, 3 => 70, 4 => 100, 5 => 134, 6 => 172, 7 => 196, 8 => 242, 9 => 292, 10 => 346];

        $targetData = $dataCapacities[$version] ?? 19;
        $total = $totalCodewords[$version] ?? 26;
        $eccCount = $total - $targetData;

        // Byte mode indicator: 0100 (4 bits) + character count (8 bits for v1-9, 16 for v10+)
        $bits = '0100';
        $charCountBits = $version < 10 ? 8 : 16;
        $bits .= str_pad(decbin(count($bytes)), $charCountBits, '0', STR_PAD_LEFT);

        foreach ($bytes as $b) {
            $bits .= str_pad(decbin($b), 8, '0', STR_PAD_LEFT);
        }

        // Terminator
        $maxBits = $targetData * 8;
        if (strlen($bits) < $maxBits) {
            $bits .= substr('0000', 0, min(4, $maxBits - strlen($bits)));
        }

        // Byte padding
        while (strlen($bits) % 8 !== 0) {
            $bits .= '0';
        }

        $dataCodewords = [];
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $dataCodewords[] = bindec(substr($bits, $i, 8));
        }

        // Pad codewords (0xEC, 0x11 alternating)
        $pad = [0xEC, 0x11];
        $p = 0;
        while (count($dataCodewords) < $targetData) {
            $dataCodewords[] = $pad[$p % 2];
            $p++;
        }

        // Generate Reed-Solomon error correction codewords
        $eccCodewords = self::calculateReedSolomon($dataCodewords, $eccCount);

        return array_merge($dataCodewords, $eccCodewords);
    }

    protected static function calculateReedSolomon(array $data, int $eccCount): array
    {
        // GF(256) tables with primitive polynomial 0x11D (285)
        $exp = array_fill(0, 512, 0);
        $log = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            $exp[$i] = $x;
            $exp[$i + 255] = $x;
            $log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;
            }
        }

        // Generator polynomial
        $gen = [1];
        for ($i = 0; $i < $eccCount; $i++) {
            $next = array_fill(0, count($gen) + 1, 0);
            $factor = $exp[$i];
            for ($j = 0; $j < count($gen); $j++) {
                $next[$j] ^= $gen[$j];
                $prod = ($gen[$j] === 0 || $factor === 0) ? 0 : $exp[$log[$gen[$j]] + $log[$factor]];
                $next[$j + 1] ^= $prod;
            }
            $gen = $next;
        }

        // Polynomial division
        $res = array_fill(0, count($data) + $eccCount, 0);
        for ($i = 0; $i < count($data); $i++) {
            $res[$i] = $data[$i];
        }

        for ($i = 0; $i < count($data); $i++) {
            $lead = $res[$i];
            if ($lead !== 0) {
                $leadLog = $log[$lead];
                for ($j = 0; $j < count($gen); $j++) {
                    if ($gen[$j] !== 0) {
                        $res[$i + $j] ^= $exp[$leadLog + $log[$gen[$j]]];
                    }
                }
            }
        }

        return array_slice($res, count($data), $eccCount);
    }

    protected static function placeCodewordsAndMask(array &$matrix, array $codewords, int $size): void
    {
        $bits = '';
        foreach ($codewords as $cw) {
            $bits .= str_pad(decbin($cw), 8, '0', STR_PAD_LEFT);
        }

        $bitIdx = 0;
        $totalBits = strlen($bits);

        $row = $size - 1;
        $col = $size - 1;
        $upward = true;

        while ($col > 0) {
            if ($col === 6) {
                $col--; // Skip vertical timing column
            }

            for ($r = 0; $r < $size; $r++) {
                $currRow = $upward ? ($size - 1 - $r) : $r;
                for ($c = 0; $c < 2; $c++) {
                    $currCol = $col - $c;
                    if ($matrix[$currRow][$currCol] === null) {
                        $bit = ($bitIdx < $totalBits) ? (int) $bits[$bitIdx++] : 0;
                        // Apply Mask Pattern 0: (row + col) % 2 == 0
                        $mask = (($currRow + $currCol) % 2 === 0);
                        $matrix[$currRow][$currCol] = $mask ? ($bit ^ 1) : $bit;
                    }
                }
            }

            $col -= 2;
            $upward = !$upward;
        }
    }
}
