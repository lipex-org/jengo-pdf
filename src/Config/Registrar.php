<?php

declare(strict_types=1);

namespace Jengo\Pdf\Config;

use Jengo\Pdf\Installers\PdfInstaller;

class Registrar
{
    public static function JengoBase(): array
    {
        return [
            'installers' => [
                PdfInstaller::class,
            ],
        ];
    }
}
