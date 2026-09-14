<?php

declare(strict_types=1);

namespace Jengo\Pdf\Filtering;

enum FilterType: string
{
    case Text = 'text';
    case Search = 'search';
    case Date = 'date';
    case DateRange = 'date_range';
    case Select = 'select';
    case Toggle = 'toggle';
    case NumberRange = 'number_range';
}
