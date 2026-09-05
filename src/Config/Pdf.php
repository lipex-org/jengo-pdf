<?php

declare(strict_types=1);

namespace Jengo\Pdf\Config;

use CodeIgniter\Config\BaseConfig;
use Jengo\Pdf\Enums\Orientation;
use Jengo\Pdf\Enums\PaperFormat;

class Pdf extends BaseConfig
{
    /**
     * Default PDF rendering driver ('dompdf' or 'chromium').
     */
    public string $driver = 'dompdf';

    /**
     * Default paper format.
     */
    public PaperFormat $defaultFormat = PaperFormat::A4;

    /**
     * Default paper orientation.
     */
    public Orientation $defaultOrientation = Orientation::PORTRAIT;

    /**
     * Default margins (in millimeters).
     *
     * @var array{top: float, right: float, bottom: float, left: float, unit?: string}
     */
    public array $defaultMargins = [
        'top'    => 10.0,
        'right'  => 10.0,
        'bottom' => 10.0,
        'left'   => 10.0,
        'unit'   => 'mm',
    ];

    /**
     * Dompdf driver options.
     *
     * @var array<string, mixed>
     */
    public array $dompdf = [
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled'      => true,
        'defaultFont'          => 'DejaVu Sans',
    ];

    /**
     * Chromium driver options.
     *
     * @var array<string, mixed>
     */
    public array $chromium = [
        'nodeBinary'     => '/usr/bin/node',
        'npmBinary'      => '/usr/bin/npm',
        'chromiumBinary' => null,
        'tempDirectory'  => null,
        'timeout'        => 30,
    ];
}
