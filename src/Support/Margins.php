<?php

declare(strict_types=1);

namespace Jengo\Pdf\Support;

class Margins
{
    public function __construct(
        public readonly float $top = 10.0,
        public readonly float $right = 10.0,
        public readonly float $bottom = 10.0,
        public readonly float $left = 10.0,
        public readonly string $unit = 'mm'
    ) {
    }

    public static function all(float $value, string $unit = 'mm'): self
    {
        return new self($value, $value, $value, $value, $unit);
    }

    public static function zero(): self
    {
        return new self(0.0, 0.0, 0.0, 0.0, 'mm');
    }

    public static function symmetric(float $vertical, float $horizontal, string $unit = 'mm'): self
    {
        return new self($vertical, $horizontal, $vertical, $horizontal, $unit);
    }

    /**
     * Get CSS margin strings, e.g. ['top' => '10mm', ...].
     *
     * @return array{top: string, right: string, bottom: string, left: string}
     */
    public function toCssArray(): array
    {
        return [
            'top'    => "{$this->top}{$this->unit}",
            'right'  => "{$this->right}{$this->unit}",
            'bottom' => "{$this->bottom}{$this->unit}",
            'left'   => "{$this->left}{$this->unit}",
        ];
    }

    /**
     * Convert margins to points (1 in = 72 pt, 1 mm = 2.83465 pt, 1 cm = 28.3465 pt).
     *
     * @return array{top: float, right: float, bottom: float, left: float}
     */
    public function toPoints(): array
    {
        $factor = match (strtolower($this->unit)) {
            'pt' => 1.0,
            'in' => 72.0,
            'mm' => 72.0 / 25.4,
            'cm' => 720.0 / 25.4,
            'px' => 72.0 / 96.0,
            default => 72.0 / 25.4,
        };

        return [
            'top'    => $this->top * $factor,
            'right'  => $this->right * $factor,
            'bottom' => $this->bottom * $factor,
            'left'   => $this->left * $factor,
        ];
    }
}
