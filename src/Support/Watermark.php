<?php

declare(strict_types=1);

namespace Jengo\Pdf\Support;

class Watermark
{
    public function __construct(
        public string $text = 'JENGO',
        public float $opacity = 0.08,
        public string $color = '#64748b',
        public int $angle = -35,
        public string $size = '64pt',
        public bool $enabled = true
    ) {
    }

    /**
     * Factory method to create Watermark instance from text, bool, or config array.
     */
    public static function make(
        string|bool|array|self $config = 'JENGO',
        float $opacity = 0.08,
        ?string $color = null,
        ?int $angle = -35,
        ?string $size = null
    ): self {
        if ($config instanceof self) {
            return $config;
        }

        if (is_bool($config)) {
            return new self(
                text: 'JENGO',
                opacity: $opacity,
                color: $color ?? '#64748b',
                angle: $angle ?? -35,
                size: $size ?? '64pt',
                enabled: $config
            );
        }

        if (is_array($config)) {
            return new self(
                text: (string) ($config['text'] ?? 'JENGO'),
                opacity: (float) ($config['opacity'] ?? $opacity),
                color: (string) ($config['color'] ?? $color ?? '#64748b'),
                angle: (int) ($config['angle'] ?? $angle ?? -35),
                size: (string) ($config['size'] ?? $size ?? '64pt'),
                enabled: (bool) ($config['enabled'] ?? true)
            );
        }

        return new self(
            text: (string) $config,
            opacity: $opacity,
            color: $color ?? '#64748b',
            angle: $angle ?? -35,
            size: $size ?? '64pt',
            enabled: true
        );
    }

    /**
     * Render the CSS styles for the watermark.
     */
    public function renderCss(): string
    {
        if (!$this->enabled || trim($this->text) === '') {
            return '';
        }

        $opacityPct = (int) round($this->opacity * 100);

        return <<<CSS
<style>
.jengo-watermark {
    position: fixed;
    top: 45%;
    left: 5%;
    right: 5%;
    transform: rotate({$this->angle}deg);
    -webkit-transform: rotate({$this->angle}deg);
    -ms-transform: rotate({$this->angle}deg);
    transform-origin: 50% 50%;
    font-size: {$this->size};
    font-weight: 900;
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    color: {$this->color};
    opacity: {$this->opacity};
    filter: alpha(opacity={$opacityPct});
    text-transform: uppercase;
    letter-spacing: 12px;
    z-index: -1000;
    pointer-events: none;
    white-space: nowrap;
    text-align: center;
    user-select: none;
}
@media print {
    .jengo-watermark {
        display: block !important;
        opacity: {$this->opacity} !important;
    }
}
</style>
CSS;
    }

    /**
     * Render the combined CSS and HTML for the watermark.
     */
    public function renderHtml(): string
    {
        if (!$this->enabled || trim($this->text) === '') {
            return '';
        }

        $escaped = htmlspecialchars($this->text, ENT_QUOTES, 'UTF-8');

        return $this->renderCss() . "\n" . '<div class="jengo-watermark">' . $escaped . '</div>' . "\n";
    }
}
