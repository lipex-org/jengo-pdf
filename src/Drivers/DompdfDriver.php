<?php

declare(strict_types=1);

namespace Jengo\Pdf\Drivers;

use Dompdf\Dompdf;
use Dompdf\Options;
use Jengo\Pdf\Contracts\DriverInterface;
use Jengo\Pdf\Exceptions\DriverException;
use Jengo\Pdf\PdfDocument;
use Throwable;

class DompdfDriver extends AbstractDriver implements DriverInterface
{
    public function __construct(
        protected array $options = []
    ) {
    }

    public function getName(): string
    {
        return 'dompdf';
    }

    public function isAvailable(): bool
    {
        return class_exists(Dompdf::class);
    }

    public function render(PdfDocument $document): string
    {
        if (!$this->isAvailable()) {
            throw new DriverException('Dompdf library is not installed. Please run "composer require dompdf/dompdf".');
        }

        try {
            $dompdfOptions = new Options();
            $dompdfOptions->set('isHtml5ParserEnabled', $this->options['isHtml5ParserEnabled'] ?? true);
            $dompdfOptions->set('isRemoteEnabled', $this->options['isRemoteEnabled'] ?? true);
            $dompdfOptions->set('defaultFont', $this->options['defaultFont'] ?? 'DejaVu Sans');
            $dompdfOptions->set('isFontSubsettingEnabled', $this->options['isFontSubsettingEnabled'] ?? false);

            $tempDir = $this->options['tempDirectory'] ?? (defined('WRITEPATH') ? WRITEPATH . 'temp/pdf' : sys_get_temp_dir() . '/jengo-pdf');
            $fontDir = $this->options['fontDir'] ?? $tempDir . '/fonts';

            if (!is_dir($fontDir)) {
                mkdir($fontDir, 0777, true);
            }

            $dompdfOptions->set('tempDir', $tempDir);
            $dompdfOptions->set('fontDir', $fontDir);
            $dompdfOptions->set('fontCache', $fontDir);

            if (isset($this->options['chroot'])) {
                $dompdfOptions->set('chroot', $this->options['chroot']);
            }

            $dompdf = new Dompdf($dompdfOptions);

            $html = $this->resolveHtml($document);

            // Wrap in margin CSS if margins are specified
            $margins = $document->getMargins();
            if ($margins !== null) {
                $cssMargins = $margins->toCssArray();
                $marginStyle = sprintf(
                    '<style>@page { margin: %s %s %s %s; }</style>',
                    $cssMargins['top'],
                    $cssMargins['right'],
                    $cssMargins['bottom'],
                    $cssMargins['left']
                );
                $html = $marginStyle . $html;
            }

            $dompdf->loadHtml($html);

            $paperFormat = $document->getFormat() ? strtolower($document->getFormat()->value) : 'a4';
            $orientation = $document->getOrientation() ? $document->getOrientation()->value : 'portrait';

            $dompdf->setPaper($paperFormat, $orientation);
            $dompdf->render();

            return (string) $dompdf->output();
        } catch (Throwable $e) {
            throw new DriverException('Dompdf failed to render document: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
