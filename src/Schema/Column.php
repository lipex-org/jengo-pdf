<?php

declare(strict_types=1);

namespace Jengo\Pdf\Schema;

class Column
{
    public function __construct(
        public string $key,
        public string $label,
        public ?string $format = null,
        public string $align = 'left',
        public ?string $width = null,
        public mixed $transformer = null,
        public bool $isSummable = false,
        public ?array $badgeMap = null
    ) {
    }

    public static function make(string $key, string|array $definition = ''): self
    {
        if (is_string($definition)) {
            $label = $definition !== '' ? $definition : ucwords(str_replace(['.', '_'], ' ', $key));
            return new self(
                key: $key,
                label: $label
            );
        }

        return new self(
            key: $key,
            label: (string) ($definition['label'] ?? ucwords(str_replace(['.', '_'], ' ', $key))),
            format: isset($definition['format']) ? (string) $definition['format'] : null,
            align: (string) ($definition['align'] ?? 'left'),
            width: isset($definition['width']) ? (string) $definition['width'] : null,
            transformer: $definition['transform'] ?? $definition['transformer'] ?? null,
            isSummable: (bool) ($definition['sum'] ?? false),
            badgeMap: $definition['badge'] ?? null
        );
    }

    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function format(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    public function align(string $align): static
    {
        $this->align = $align;
        return $this;
    }

    public function width(string $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function transform(callable $transformer): static
    {
        $this->transformer = $transformer;
        return $this;
    }

    public function currency(?string $symbol = '$'): static
    {
        $this->format = 'currency:' . ($symbol ?? '$');
        return $this;
    }

    public function date(?string $format = 'Y-m-d'): static
    {
        $this->format = 'date:' . ($format ?? 'Y-m-d');
        return $this;
    }

    public function datetime(?string $format = 'Y-m-d H:i'): static
    {
        $this->format = 'datetime:' . ($format ?? 'Y-m-d H:i');
        return $this;
    }

    public function number(int $decimals = 0): static
    {
        $this->format = 'number:' . $decimals;
        return $this;
    }

    public function boolean(string $true = 'Yes', string $false = 'No'): static
    {
        $this->format = 'boolean:' . $true . '|' . $false;
        return $this;
    }

    public function badge(array $mapping = []): static
    {
        $this->badgeMap = $mapping;
        return $this;
    }

    public function sum(): static
    {
        $this->isSummable = true;
        return $this;
    }

    public function isSummable(): bool
    {
        return $this->isSummable;
    }

    public function formatValue(mixed $value, mixed $row = null): string
    {
        if ($this->transformer !== null && is_callable($this->transformer)) {
            return (string) ($this->transformer)($value, $row);
        }

        if ($this->badgeMap !== null) {
            $valKey = strtolower((string) $value);
            $badgeType = $this->badgeMap[$valKey] ?? $this->badgeMap[$value] ?? 'default';
            
            $bgColors = [
                'success' => '#ecfdf5',
                'warning' => '#fffbeb',
                'danger'  => '#fef2f2',
                'info'    => '#eff6ff',
                'default' => '#f1f5f9',
            ];
            $textColors = [
                'success' => '#059669',
                'warning' => '#d97706',
                'danger'  => '#dc2626',
                'info'    => '#2563eb',
                'default' => '#475569',
            ];
            $bg = $bgColors[$badgeType] ?? '#f1f5f9';
            $fg = $textColors[$badgeType] ?? '#475569';
            $display = htmlspecialchars(ucfirst((string) $value));

            return "<span style=\"display:inline-block;padding:2px 8px;border-radius:4px;font-size:9px;font-weight:bold;text-transform:uppercase;background-color:{$bg};color:{$fg};\">{$display}</span>";
        }

        if ($value === null) {
            return '-';
        }

        if ($this->format !== null) {
            $parts = explode(':', $this->format, 2);
            $type = strtolower($parts[0]);
            $param = $parts[1] ?? null;

            return match ($type) {
                'date' => $value instanceof \DateTimeInterface
                    ? $value->format($param ?: 'Y-m-d')
                    : date($param ?: 'Y-m-d', is_numeric($value) ? (int) $value : strtotime((string) $value)),
                'datetime' => $value instanceof \DateTimeInterface
                    ? $value->format($param ?: 'Y-m-d H:i')
                    : date($param ?: 'Y-m-d H:i', is_numeric($value) ? (int) $value : strtotime((string) $value)),
                'currency' => ($param ?: '$') . ' ' . number_format((float) $value, 2),
                'number' => number_format((float) $value, $param !== null ? (int) $param : 0),
                'boolean' => $value ? ($param ? explode('|', $param)[0] : 'Yes') : ($param ? explode('|', $param)[1] : 'No'),
                default => (string) $value,
            };
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }
}
