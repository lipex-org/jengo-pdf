<?php

declare(strict_types=1);

namespace Jengo\Pdf\Schema;

use Jengo\Pdf\Config\Pdf as ConfigPdf;

class ReportTheme
{
    /**
     * Globally registered named themes.
     *
     * @var array<string, ReportTheme>
     */
    protected static array $registry = [];

    public function __construct(
        public string $primary = '#3182ce',
        public string $secondary = '#2b6cb0',
        public string $headerText = '#ffffff',
        public string $zebra = '#f7fafc',
        public string $border = '#e2e8f0',
        public string $font = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
        public string $footerText = '#a0aec0',
        public ?string $customCss = null
    ) {
    }

    /**
     * Creates a ReportTheme instance from an object, array, or registered theme name.
     */
    public static function make(mixed $definition = null): self
    {
        if ($definition instanceof self) {
            return $definition;
        }

        if (is_string($definition)) {
            // Hex color shorthand (e.g. '#6366f1')
            if (str_starts_with($definition, '#')) {
                return new self(
                    primary: $definition,
                    secondary: $definition
                );
            }

            // Check globally registered themes
            if (isset(static::$registry[$definition])) {
                return static::$registry[$definition];
            }

            // Check config themes
            $config = config('Pdf') ?? new ConfigPdf();
            if (isset($config->themes[$definition])) {
                return static::fromArray($config->themes[$definition]);
            }
        }

        if (is_array($definition)) {
            return static::fromArray($definition);
        }

        return new self();
    }

    /**
     * Create a theme instance from an associative array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            primary: $data['primary'] ?? $data['color'] ?? '#3182ce',
            secondary: $data['secondary'] ?? $data['primary'] ?? '#2b6cb0',
            headerText: $data['headerText'] ?? $data['header_text'] ?? '#ffffff',
            zebra: $data['zebra'] ?? $data['zebra_color'] ?? '#f7fafc',
            border: $data['border'] ?? $data['border_color'] ?? '#e2e8f0',
            font: $data['font'] ?? $data['font_family'] ?? '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
            footerText: $data['footerText'] ?? $data['footer_text'] ?? '#a0aec0',
            customCss: $data['customCss'] ?? $data['css'] ?? null
        );
    }

    /**
     * Register a custom named theme globally.
     */
    public static function register(string $name, array|self $theme): void
    {
        static::$registry[$name] = $theme instanceof self ? $theme : static::fromArray($theme);
    }

    /**
     * Clear all globally registered themes.
     */
    public static function clearRegistry(): void
    {
        static::$registry = [];
    }

    public function primary(string $color): static
    {
        $this->primary = $color;
        return $this;
    }

    public function secondary(string $color): static
    {
        $this->secondary = $color;
        return $this;
    }

    public function headerText(string $color): static
    {
        $this->headerText = $color;
        return $this;
    }

    public function zebra(string $color): static
    {
        $this->zebra = $color;
        return $this;
    }

    public function border(string $color): static
    {
        $this->border = $color;
        return $this;
    }

    public function font(string $fontFamily): static
    {
        $this->font = $fontFamily;
        return $this;
    }

    public function footerText(string $color): static
    {
        $this->footerText = $color;
        return $this;
    }

    public function customCss(string $css): static
    {
        $this->customCss = $css;
        return $this;
    }
}
