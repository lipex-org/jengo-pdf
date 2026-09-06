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

    /**
     * Standard View Templates Mapping.
     * Maps template aliases to namespaced view paths.
     * Developers can override any view here by specifying their own custom view path.
     *
     * @var array<string, string>
     */
    public array $views = [
        'invoice'        => 'Jengo\Pdf\Views\invoice',
        'quotation'      => 'Jengo\Pdf\Views\quotation',
        'receipt'        => 'Jengo\Pdf\Views\receipt',
        'delivery_note'  => 'Jengo\Pdf\Views\delivery_note',
        'payslip'        => 'Jengo\Pdf\Views\payslip',
        'purchase_order' => 'Jengo\Pdf\Views\purchase_order',
        'certificate'    => 'Jengo\Pdf\Views\certificate',
        'report'         => 'Jengo\Pdf\Views\report',
    ];

    /**
     * Schema Report Themes.
     *
     * @var array<string, array<string, string>>
     */
    public array $themes = [
        'modern-blue' => [
            'primary'    => '#3182ce',
            'secondary'  => '#2b6cb0',
            'headerText' => '#ffffff',
            'zebra'      => '#f7fafc',
            'border'     => '#e2e8f0',
            'font'       => 'DejaVu Sans, Helvetica, Arial, sans-serif',
            'footerText' => '#a0aec0',
        ],
        'emerald' => [
            'primary'    => '#059669',
            'secondary'  => '#047857',
            'headerText' => '#ffffff',
            'zebra'      => '#f0fdf4',
            'border'     => '#d1fae5',
            'font'       => 'DejaVu Sans, Helvetica, Arial, sans-serif',
            'footerText' => '#6ee7b7',
        ],
        'crimson' => [
            'primary'    => '#e11d48',
            'secondary'  => '#be123c',
            'headerText' => '#ffffff',
            'zebra'      => '#fff1f2',
            'border'     => '#fecdd3',
            'font'       => 'DejaVu Sans, Helvetica, Arial, sans-serif',
            'footerText' => '#fda4af',
        ],
        'amber' => [
            'primary'    => '#d97706',
            'secondary'  => '#b45309',
            'headerText' => '#ffffff',
            'zebra'      => '#fffbeb',
            'border'     => '#fde68a',
            'font'       => 'DejaVu Sans, Helvetica, Arial, sans-serif',
            'footerText' => '#fcd34d',
        ],
        'indigo' => [
            'primary'    => '#6366f1',
            'secondary'  => '#4f46e5',
            'headerText' => '#ffffff',
            'zebra'      => '#eef2ff',
            'border'     => '#c7d2fe',
            'font'       => 'DejaVu Sans, Helvetica, Arial, sans-serif',
            'footerText' => '#a5b4fc',
        ],
        'slate' => [
            'primary'    => '#475569',
            'secondary'  => '#334155',
            'headerText' => '#ffffff',
            'zebra'      => '#f8fafc',
            'border'     => '#e2e8f0',
            'font'       => 'DejaVu Sans, Helvetica, Arial, sans-serif',
            'footerText' => '#94a3b8',
        ],
        'minimal-dark' => [
            'primary'    => '#0f172a',
            'secondary'  => '#1e293b',
            'headerText' => '#ffffff',
            'zebra'      => '#f1f5f9',
            'border'     => '#cbd5e1',
            'font'       => 'DejaVu Sans, Helvetica, Arial, sans-serif',
            'footerText' => '#64748b',
        ],
    ];
}
