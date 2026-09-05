<?php

declare(strict_types=1);

namespace Jengo\Pdf\Enums;

enum PaperFormat: string
{
    case A0 = 'A0';
    case A1 = 'A1';
    case A2 = 'A2';
    case A3 = 'A3';
    case A4 = 'A4';
    case A5 = 'A5';
    case A6 = 'A6';
    case A7 = 'A7';
    case A8 = 'A8';
    case LETTER = 'Letter';
    case LEGAL = 'Legal';
    case TABLOID = 'Tabloid';
    case EXECUTIVE = 'Executive';
    case LEDGER = 'Ledger';

    public static function fromName(string $name): self
    {
        $upper = strtoupper($name);
        foreach (self::cases() as $case) {
            if (strtoupper($case->name) === $upper || strtoupper($case->value) === $upper) {
                return $case;
            }
        }

        return self::from($name);
    }

    /**
     * Get dimensions in points [width, height] (72 points per inch).
     *
     * @return array{0: float, 1: float}
     */
    public function getDimensionsInPoints(): array
    {
        return match ($this) {
            self::A0 => [2383.94, 3370.39],
            self::A1 => [1683.78, 2383.94],
            self::A2 => [1190.55, 1683.78],
            self::A3 => [841.89, 1190.55],
            self::A4 => [595.28, 841.89],
            self::A5 => [419.53, 595.28],
            self::A6 => [297.64, 419.53],
            self::A7 => [209.76, 297.64],
            self::A8 => [147.40, 209.76],
            self::LETTER => [612.0, 792.0],
            self::LEGAL => [612.0, 1008.0],
            self::TABLOID => [792.0, 1224.0],
            self::EXECUTIVE => [522.0, 756.0],
            self::LEDGER => [1224.0, 792.0],
        };
    }

    /**
     * Get dimensions in millimeters [width, height].
     *
     * @return array{0: float, 1: float}
     */
    public function getDimensionsInMillimeters(): array
    {
        return match ($this) {
            self::A0 => [841.0, 1189.0],
            self::A1 => [594.0, 841.0],
            self::A2 => [420.0, 594.0],
            self::A3 => [297.0, 420.0],
            self::A4 => [210.0, 297.0],
            self::A5 => [148.0, 210.0],
            self::A6 => [105.0, 148.0],
            self::A7 => [74.0, 105.0],
            self::A8 => [52.0, 74.0],
            self::LETTER => [215.9, 279.4],
            self::LEGAL => [215.9, 355.6],
            self::TABLOID => [279.4, 431.8],
            self::EXECUTIVE => [184.15, 266.7],
            self::LEDGER => [431.8, 279.4],
        };
    }
}
