<?php

declare(strict_types=1);

namespace Jengo\Pdf\Filtering;

class FilterField
{
    /**
     * @param array<string|int, string> $options Key-value options for select controls
     */
    public function __construct(
        public string $name,
        public string $label,
        public FilterType|string $type = FilterType::Text,
        public mixed $default = null,
        public string $placeholder = '',
        public array $options = [],
        public bool $multiple = false,
        public ?float $min = null,
        public ?float $max = null,
        public ?float $step = 1.0,
    ) {
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;
        return $this;
    }

    public function options(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function min(?float $min): static
    {
        $this->min = $min;
        return $this;
    }

    public function max(?float $max): static
    {
        $this->max = $max;
        return $this;
    }

    public function step(?float $step): static
    {
        $this->step = $step;
        return $this;
    }

    public function getType(): string
    {
        return $this->type instanceof FilterType ? $this->type->value : (string) $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'        => $this->name,
            'label'       => $this->label,
            'type'        => $this->getType(),
            'default'     => $this->default,
            'placeholder' => $this->placeholder,
            'options'     => $this->options,
            'multiple'    => $this->multiple,
            'min'         => $this->min,
            'max'         => $this->max,
            'step'        => $this->step,
        ];
    }

    /**
     * Render the HTML input control for the slide-over drawer.
     */
    public function renderHtml(mixed $currentValue = null): string
    {
        $val = $currentValue ?? $this->default;
        $type = $this->getType();
        $safeName = htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8');
        $safeLabel = htmlspecialchars($this->label, ENT_QUOTES, 'UTF-8');
        $safePlaceholder = htmlspecialchars($this->placeholder, ENT_QUOTES, 'UTF-8');

        $output = "<div class=\"jengo-filter-group\" data-filter-name=\"{$safeName}\" data-filter-type=\"{$type}\">";
        $output .= "<label class=\"jengo-filter-label\" for=\"filter_{$safeName}\">{$safeLabel}</label>";

        switch ($type) {
            case 'search':
                $valStr = is_scalar($val) ? htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8') : '';
                $output .= "<div style=\"position: relative; width: 100%;\">";
                $output .= "<input type=\"text\" class=\"jengo-filter-input\" id=\"filter_{$safeName}\" name=\"{$safeName}\" value=\"{$valStr}\" placeholder=\"{$safePlaceholder}\" autocomplete=\"off\">";
                $output .= "</div>";
                break;

            case 'date':
                $valStr = is_scalar($val) ? htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8') : '';
                $output .= "<input type=\"date\" class=\"jengo-filter-input\" id=\"filter_{$safeName}\" name=\"{$safeName}\" value=\"{$valStr}\">";
                break;

            case 'date_range':
                $fromVal = is_array($val) && isset($val['from']) ? htmlspecialchars((string) $val['from'], ENT_QUOTES, 'UTF-8') : '';
                $toVal = is_array($val) && isset($val['to']) ? htmlspecialchars((string) $val['to'], ENT_QUOTES, 'UTF-8') : '';
                $output .= "<div class=\"jengo-filter-range-grid\">";
                $output .= "<div><span style=\"font-size: 10px; color: #64748b;\">From</span><input type=\"date\" class=\"jengo-filter-input\" name=\"{$safeName}[from]\" value=\"{$fromVal}\"></div>";
                $output .= "<div><span style=\"font-size: 10px; color: #64748b;\">To</span><input type=\"date\" class=\"jengo-filter-input\" name=\"{$safeName}[to]\" value=\"{$toVal}\"></div>";
                $output .= "</div>";
                break;

            case 'select':
                $output .= "<select class=\"jengo-filter-select\" id=\"filter_{$safeName}\" name=\"{$safeName}\"" . ($this->multiple ? ' multiple' : '') . ">";
                foreach ($this->options as $optKey => $optLabel) {
                    $optKeyStr = (string) $optKey;
                    $selected = ($val == $optKeyStr || (is_array($val) && in_array($optKeyStr, $val, true))) ? ' selected' : '';
                    $output .= "<option value=\"" . htmlspecialchars($optKeyStr, ENT_QUOTES, 'UTF-8') . "\"{$selected}>" . htmlspecialchars((string) $optLabel, ENT_QUOTES, 'UTF-8') . "</option>";
                }
                $output .= "</select>";
                break;

            case 'toggle':
                $checked = !empty($val) ? ' checked' : '';
                $output .= "<label class=\"jengo-toggle-switch\">";
                $output .= "<span style=\"font-size: 12px; color: #cbd5e1;\">Enabled</span>";
                $output .= "<input type=\"checkbox\" class=\"jengo-toggle-input\" id=\"filter_{$safeName}\" name=\"{$safeName}\" value=\"1\"{$checked}>";
                $output .= "</label>";
                break;

            case 'number_range':
                $minVal = is_array($val) && isset($val['min']) ? htmlspecialchars((string) $val['min'], ENT_QUOTES, 'UTF-8') : '';
                $maxVal = is_array($val) && isset($val['max']) ? htmlspecialchars((string) $val['max'], ENT_QUOTES, 'UTF-8') : '';
                $output .= "<div class=\"jengo-filter-range-grid\">";
                $output .= "<div><span style=\"font-size: 10px; color: #64748b;\">Min</span><input type=\"number\" class=\"jengo-filter-input\" name=\"{$safeName}[min]\" value=\"{$minVal}\"" . ($this->min !== null ? " min=\"{$this->min}\"" : '') . ($this->step !== null ? " step=\"{$this->step}\"" : '') . "></div>";
                $output .= "<div><span style=\"font-size: 10px; color: #64748b;\">Max</span><input type=\"number\" class=\"jengo-filter-input\" name=\"{$safeName}[max]\" value=\"{$maxVal}\"" . ($this->max !== null ? " max=\"{$this->max}\"" : '') . ($this->step !== null ? " step=\"{$this->step}\"" : '') . "></div>";
                $output .= "</div>";
                break;

            case 'text':
            default:
                $valStr = is_scalar($val) ? htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8') : '';
                $output .= "<input type=\"text\" class=\"jengo-filter-input\" id=\"filter_{$safeName}\" name=\"{$safeName}\" value=\"{$valStr}\" placeholder=\"{$safePlaceholder}\">";
                break;
        }

        $output .= "</div>";

        return $output;
    }
}
