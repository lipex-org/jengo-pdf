<?php

declare(strict_types=1);

namespace Jengo\Pdf\Drivers;

use Jengo\Pdf\Contracts\DriverInterface;
use Jengo\Pdf\Exceptions\BinaryNotFoundException;
use Jengo\Pdf\Exceptions\DriverException;
use Jengo\Pdf\PdfDocument;
use Symfony\Component\Process\Process;
use Throwable;

class ChromiumDriver extends AbstractDriver implements DriverInterface
{
    public function __construct(
        protected array $options = []
    ) {
    }

    public function getName(): string
    {
        return 'chromium';
    }

    public function isAvailable(): bool
    {
        $nodeBinary = $this->resolveNodeBinary();
        return $nodeBinary !== null;
    }

    public function render(PdfDocument $document): string
    {
        $nodeBinary = $this->resolveNodeBinary();
        if ($nodeBinary === null) {
            throw new BinaryNotFoundException(
                'Node.js binary was not found. Please install Node.js and Puppeteer ("npm install -g puppeteer") or switch to the "dompdf" driver.'
            );
        }

        $tempDir = $this->options['tempDirectory'] ?? (defined('WRITEPATH') ? WRITEPATH . 'temp/pdf' : sys_get_temp_dir() . '/jengo-pdf');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $uniqueId = bin2hex(random_bytes(8));
        $configPath = $tempDir . '/pdf-config-' . $uniqueId . '.json';
        $outputPath = $tempDir . '/pdf-output-' . $uniqueId . '.pdf';

        $margins = $document->getMargins();
        $marginArray = $margins ? $margins->toCssArray() : [
            'top'    => '10mm',
            'right'  => '10mm',
            'bottom' => '10mm',
            'left'   => '10mm',
        ];

        $payload = [
            'outputPath'       => $outputPath,
            'format'           => $document->getFormat() ? $document->getFormat()->value : 'A4',
            'landscape'        => $document->getOrientation() ? $document->getOrientation()->isLandscape() : false,
            'printBackground'  => $document->hasBackground(),
            'scale'            => $document->getScale(),
            'margin'           => $marginArray,
            'mediaType'        => $document->getMediaType() ? $document->getMediaType()->value : 'screen',
            'timeout'          => $this->options['timeout'] ?? 30,
            'executablePath'   => $this->options['chromiumBinary'] ?? null,
            'waitForSelector'  => $document->getWaitForSelector(),
            'waitForTimeout'   => $document->getWaitForTimeout(),
        ];

        if ($document->getUrl() !== null) {
            $payload['url'] = $document->getUrl();
        } else {
            $payload['html'] = $this->resolveHtml($document);
        }

        if ($document->getHeader() !== null) {
            $payload['headerTemplate'] = $document->getHeader()->renderHtml();
        }
        if ($document->getFooter() !== null) {
            $payload['footerTemplate'] = $document->getFooter()->renderHtml();
        }

        file_put_contents($configPath, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        $scriptPath = __DIR__ . '/Resources/chromium-pdf.cjs';

        try {
            $process = new Process([$nodeBinary, $scriptPath, $configPath]);
            $process->setTimeout((float) ($this->options['timeout'] ?? 60));
            $process->run();

            if (!$process->isSuccessful()) {
                $errorOutput = trim($process->getErrorOutput() ?: $process->getOutput());
                throw new DriverException('Chromium PDF rendering failed: ' . $errorOutput);
            }

            if (!file_exists($outputPath)) {
                throw new DriverException('Chromium driver executed but output PDF file was not generated.');
            }

            $pdfContents = (string) file_get_contents($outputPath);

            return $pdfContents;
        } catch (Throwable $e) {
            if ($e instanceof DriverException) {
                throw $e;
            }
            throw new DriverException('Chromium execution error: ' . $e->getMessage(), (int) $e->getCode(), $e);
        } finally {
            if (file_exists($configPath)) {
                @unlink($configPath);
            }
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }
        }
    }

    protected function resolveNodeBinary(): ?string
    {
        if (!empty($this->options['nodeBinary']) && is_executable($this->options['nodeBinary'])) {
            return $this->options['nodeBinary'];
        }

        $candidates = ['node', 'nodejs', '/usr/bin/node', '/usr/local/bin/node'];

        foreach ($candidates as $candidate) {
            $process = new Process(['which', $candidate]);
            $process->run();
            if ($process->isSuccessful()) {
                $path = trim($process->getOutput());
                if (!empty($path) && is_executable($path)) {
                    return $path;
                }
            }
        }

        return null;
    }
}
