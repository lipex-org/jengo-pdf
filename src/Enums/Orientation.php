<?php

declare(strict_types=1);

namespace Jengo\Pdf\Enums;

enum Orientation: string
{
    case PORTRAIT = 'portrait';
    case LANDSCAPE = 'landscape';

    public function isPortrait(): bool
    {
        return $this === self::PORTRAIT;
    }

    public function isLandscape(): bool
    {
        return $this === self::LANDSCAPE;
    }
}
