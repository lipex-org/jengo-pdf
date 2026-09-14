<?php

declare(strict_types=1);

namespace Jengo\Pdf\Filtering;

class Filter
{
    public static function text(
        string $name,
        string $label,
        mixed $default = null,
        string $placeholder = ''
    ): FilterField {
        return new FilterField(
            name: $name,
            label: $label,
            type: FilterType::Text,
            default: $default,
            placeholder: $placeholder
        );
    }

    public static function search(
        string $name = 'search',
        string $label = 'Search',
        mixed $default = null,
        string $placeholder = 'Search document...'
    ): FilterField {
        return new FilterField(
            name: $name,
            label: $label,
            type: FilterType::Search,
            default: $default,
            placeholder: $placeholder
        );
    }

    public static function date(
        string $name,
        string $label,
        ?string $default = null
    ): FilterField {
        return new FilterField(
            name: $name,
            label: $label,
            type: FilterType::Date,
            default: $default
        );
    }

    /**
     * @param array{from?: string, to?: string}|null $default
     */
    public static function dateRange(
        string $name,
        string $label,
        ?array $default = null
    ): FilterField {
        return new FilterField(
            name: $name,
            label: $label,
            type: FilterType::DateRange,
            default: $default
        );
    }

    /**
     * @param array<string|int, string> $options
     */
    public static function select(
        string $name,
        string $label,
        array $options,
        mixed $default = null,
        bool $multiple = false
    ): FilterField {
        return new FilterField(
            name: $name,
            label: $label,
            type: FilterType::Select,
            default: $default,
            options: $options,
            multiple: $multiple
        );
    }

    public static function toggle(
        string $name,
        string $label,
        bool $default = false
    ): FilterField {
        return new FilterField(
            name: $name,
            label: $label,
            type: FilterType::Toggle,
            default: $default
        );
    }

    /**
     * @param array{min?: float, max?: float}|null $default
     */
    public static function numberRange(
        string $name,
        string $label,
        ?array $default = null,
        ?float $min = null,
        ?float $max = null,
        ?float $step = 1.0
    ): FilterField {
        return new FilterField(
            name: $name,
            label: $label,
            type: FilterType::NumberRange,
            default: $default,
            min: $min,
            max: $max,
            step: $step
        );
    }
}
