<?php

declare(strict_types=1);

namespace Jengo\Pdf;

use CodeIgniter\HTTP\ResponseInterface;
use Jengo\Pdf\Config\Pdf as ConfigPdf;
use Jengo\Pdf\Contracts\DriverInterface;
use Jengo\Pdf\Contracts\PdfInterface;
use Jengo\Pdf\Drivers\ChromiumDriver;
use Jengo\Pdf\Drivers\DompdfDriver;
use Jengo\Pdf\Enums\MediaType;
use Jengo\Pdf\Enums\Orientation;
use Jengo\Pdf\Enums\PaperFormat;
use Jengo\Pdf\Exceptions\DriverException;
use Jengo\Pdf\Support\HeaderFooter;
use Jengo\Pdf\Support\Margins;
use Throwable;

class PdfDocument implements PdfInterface
{
    protected ?string $html = null;
    protected ?string $view = null;
    protected array $viewData = [];
    protected ?string $templateName = null;
    protected ?string $url = null;

    protected ?PaperFormat $format = null;
    protected ?Orientation $orientation = null;
    protected ?Margins $margins = null;
    protected float $scale = 1.0;
    protected bool $background = true;
    protected ?MediaType $mediaType = null;

    protected ?HeaderFooter $header = null;
    protected ?HeaderFooter $footer = null;

    protected ?string $waitForSelector = null;
    protected ?int $waitForTimeout = null;

    protected string|DriverInterface|null $driver = null;
    protected ?string $filename = null;
    protected ?string $renderedOutput = null;

    public function __construct(
        protected ?ConfigPdf $config = null
    ) {
        $this->config ??= config('Pdf') ?? new ConfigPdf();
        $this->format = $this->config->defaultFormat;
        $this->orientation = $this->config->defaultOrientation;

        $defMargins = $this->config->defaultMargins;
        $this->margins = new Margins(
            top: (float) ($defMargins['top'] ?? 10.0),
            right: (float) ($defMargins['right'] ?? 10.0),
            bottom: (float) ($defMargins['bottom'] ?? 10.0),
            left: (float) ($defMargins['left'] ?? 10.0),
            unit: (string) ($defMargins['unit'] ?? 'mm')
        );

        $this->driver = $this->config->driver;
    }

    public function driver(string|DriverInterface $driver): static
    {
        $this->driver = $driver;
        $this->renderedOutput = null;

        return $this;
    }

    public function html(string $html): static
    {
        $this->html = $html;
        $this->view = null;
        $this->templateName = null;
        $this->url = null;
        $this->renderedOutput = null;

        return $this;
    }

    public function view(string $view, array $data = []): static
    {
        $this->view = $view;
        $this->viewData = $data;
        $this->html = null;
        $this->url = null;
        $this->renderedOutput = null;

        return $this;
    }

    /**
     * Render a standard document template configured in Config\Pdf::$templating.
     */
    public function template(string $name, array $data = []): static
    {
        $this->templateName = $name;

        $views = array_merge(
            is_array($this->config->views ?? null) ? $this->config->views : [],
            is_array($this->config->templating['views'] ?? null) ? $this->config->templating['views'] : []
        );
        if (!isset($views[$name])) {
            throw new \InvalidArgumentException("Unknown PDF template [{$name}]. Register it in Config\\Pdf::\$templating['views'] or use Pdf::view() instead.");
        }

        $brand = $this->config->templating['brand'] ?? [];
        $styles = $this->config->templating['styles'] ?? [];
        $defaults = $this->config->templating['defaults'] ?? [];

        // Merge company / brand info
        if (isset($data['company']) && is_array($data['company'])) {
            $data['company'] = array_merge($brand, $data['company']);
        } else {
            $data['company'] = $brand;
        }

        // Merge styling defaults
        $data['primaryColor'] ??= $styles['primary_color'] ?? '#0284c7';
        $data['fontFamily'] ??= $styles['font_family'] ?? 'DejaVu Sans, Helvetica, Arial, sans-serif';
        $data['brand'] ??= $brand;
        $data['styles'] ??= $styles;

        // Merge regional formatting defaults
        $data['currency'] ??= $defaults['currency'] ?? '$';
        $data['dateFormat'] ??= $defaults['date_format'] ?? 'M d, Y';

        // Merge footer / disclaimer defaults
        $data['footerText'] ??= $brand['footer_text'] ?? null;
        $data['showPoweredBy'] ??= (bool) ($brand['show_powered_by'] ?? false);

        return $this->view($views[$name], $data);
    }

    public function url(string $url): static
    {
        $this->url = $url;
        $this->html = null;
        $this->view = null;
        $this->templateName = null;
        $this->renderedOutput = null;

        return $this;
    }

    public function format(PaperFormat|string $format): static
    {
        $this->format = is_string($format) ? PaperFormat::fromName($format) : $format;
        $this->renderedOutput = null;

        return $this;
    }

    public function orientation(Orientation|string $orientation): static
    {
        $this->orientation = is_string($orientation) ? Orientation::from(strtolower($orientation)) : $orientation;
        $this->renderedOutput = null;

        return $this;
    }

    public function portrait(): static
    {
        return $this->orientation(Orientation::PORTRAIT);
    }

    public function landscape(): static
    {
        return $this->orientation(Orientation::LANDSCAPE);
    }

    public function margins(float|Margins $top = 10.0, float $right = 10.0, float $bottom = 10.0, float $left = 10.0, string $unit = 'mm'): static
    {
        if ($top instanceof Margins) {
            $this->margins = $top;
        } else {
            $this->margins = new Margins($top, $right, $bottom, $left, $unit);
        }

        $this->renderedOutput = null;

        return $this;
    }

    public function scale(float $scale): static
    {
        $this->scale = $scale;
        $this->renderedOutput = null;

        return $this;
    }

    public function background(bool $showBackground = true): static
    {
        $this->background = $showBackground;
        $this->renderedOutput = null;

        return $this;
    }

    public function emulateMedia(MediaType|string $media): static
    {
        $this->mediaType = is_string($media) ? MediaType::from(strtolower($media)) : $media;
        $this->renderedOutput = null;

        return $this;
    }

    public function header(string $html, float $height = 15.0, string $unit = 'mm'): static
    {
        $this->header = HeaderFooter::fromHtml($html, $height, $unit);
        $this->renderedOutput = null;

        return $this;
    }

    public function footer(string $html, float $height = 15.0, string $unit = 'mm'): static
    {
        $this->footer = HeaderFooter::fromHtml($html, $height, $unit);
        $this->renderedOutput = null;

        return $this;
    }

    public function headerView(string $view, array $data = [], float $height = 15.0, string $unit = 'mm'): static
    {
        $this->header = HeaderFooter::fromView($view, $data, $height, $unit);
        $this->renderedOutput = null;

        return $this;
    }

    public function footerView(string $view, array $data = [], float $height = 15.0, string $unit = 'mm'): static
    {
        $this->footer = HeaderFooter::fromView($view, $data, $height, $unit);
        $this->renderedOutput = null;

        return $this;
    }

    public function pageNumbers(string $format = '{page} / {pages}'): static
    {
        $html = sprintf(
            '<div style="width: 100%%; text-align: right; font-size: 8pt; color: #718096; padding-right: 10mm;">%s</div>',
            htmlspecialchars($format)
        );

        return $this->footer($html);
    }

    public function waitForSelector(string $selector): static
    {
        $this->waitForSelector = $selector;
        $this->renderedOutput = null;

        return $this;
    }

    public function waitForTimeout(int $milliseconds): static
    {
        $this->waitForTimeout = $milliseconds;
        $this->renderedOutput = null;

        return $this;
    }

    public function resolveDriver(): DriverInterface
    {
        if ($this->driver instanceof DriverInterface) {
            return $this->driver;
        }

        $driverName = is_string($this->driver) ? strtolower($this->driver) : 'dompdf';

        return match ($driverName) {
            'dompdf'   => new DompdfDriver($this->config->dompdf ?? []),
            'chromium' => new ChromiumDriver($this->config->chromium ?? []),
            default    => throw new DriverException("Unknown PDF driver [{$driverName}]."),
        };
    }

    public function render(): string
    {
        if (Pdf::isFaking()) {
            Pdf::getFake()?->record($this, 'render');
            return "%PDF-1.4 Fake PDF Output\n%%EOF";
        }

        if ($this->renderedOutput !== null) {
            return $this->renderedOutput;
        }

        $driver = $this->resolveDriver();
        $this->renderedOutput = $driver->render($this);

        return $this->renderedOutput;
    }

    public function output(): string
    {
        if (Pdf::isFaking()) {
            Pdf::getFake()?->record($this, 'output');
            return "%PDF-1.4 Fake PDF Output\n%%EOF";
        }

        return $this->render();
    }

    public function base64(): string
    {
        return base64_encode($this->output());
    }

    public function dataUri(): string
    {
        return 'data:application/pdf;base64,' . $this->base64();
    }

    public function toHtml(): string
    {
        if ($this->html !== null) {
            return $this->html;
        }

        if ($this->view !== null) {
            return view($this->view, $this->viewData);
        }

        return '';
    }

    public function preview(bool $withToolbar = true): ResponseInterface
    {
        if (Pdf::isFaking()) {
            Pdf::getFake()?->record($this, 'preview');
            /** @var ResponseInterface $response */
            $response = service('response');
            return $response->setHeader('Content-Type', 'text/html; charset=UTF-8')->setBody($this->toHtml());
        }

        $rawHtml = $this->toHtml();

        if (!$withToolbar) {
            /** @var ResponseInterface $response */
            $response = service('response');
            return $response->setHeader('Content-Type', 'text/html; charset=UTF-8')->setBody($rawHtml);
        }

        $format = $this->format?->value ?? 'A4';
        $orientation = $this->orientation?->value ?? 'portrait';
        $isLandscape = $this->orientation?->isLandscape() ?? false;
        $widthMm = $isLandscape ? '297mm' : '210mm';
        $minHeightMm = $isLandscape ? '210mm' : '297mm';

        $previewHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jengo PDF Preview - {$format} ({$orientation})</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            background-color: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #e2e8f0;
        }
        .jengo-preview-toolbar {
            position: sticky;
            top: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #334155;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }
        .jengo-toolbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .jengo-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: #1e293b;
            border: 1px solid #475569;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            color: #38bdf8;
            letter-spacing: 0.5px;
        }
        .jengo-toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .jengo-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: #0284c7;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .jengo-btn:hover { background: #0369a1; }
        .jengo-btn-secondary {
            background: #334155;
            color: #f1f5f9;
        }
        .jengo-btn-secondary:hover { background: #475569; }
        .jengo-preview-canvas {
            display: flex;
            justify-content: center;
            padding: 40px 20px 80px;
            overflow-x: auto;
        }
        .jengo-sheet-frame {
            width: {$widthMm};
            min-height: {$minHeightMm};
            background: #ffffff;
            color: #000000;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            position: relative;
            transform-origin: top center;
        }
        @media print {
            .jengo-preview-toolbar { display: none !important; }
            body { background: transparent !important; }
            .jengo-preview-canvas { padding: 0 !important; }
            .jengo-sheet-frame { box-shadow: none !important; width: 100% !important; margin: 0 !important; }
        }
    </style>
</head>
<body>
    <div class="jengo-preview-toolbar">
        <div class="jengo-toolbar-left">
            <div style="font-weight: 800; font-size: 14px; color: #ffffff; display: flex; align-items: center; gap: 6px;">
                <span style="color: #38bdf8;">Jengo</span>PDF Preview
            </div>
            <div class="jengo-badge">📄 {$format} &bull; {$orientation}</div>
        </div>
        <div class="jengo-toolbar-actions">
            <button onclick="window.print()" class="jengo-btn jengo-btn-secondary">🖨️ Print / Emulate</button>
            <button onclick="document.querySelector('.jengo-sheet-frame').style.width = document.querySelector('.jengo-sheet-frame').style.width === '100%' ? '{$widthMm}' : '100%'" class="jengo-btn jengo-btn-secondary">⛶ Fit Width</button>
        </div>
    </div>
    <div class="jengo-preview-canvas">
        <div class="jengo-sheet-frame">
            {$rawHtml}
        </div>
    </div>
</body>
</html>
HTML;

        /** @var ResponseInterface $response */
        $response = service('response');
        return $response->setHeader('Content-Type', 'text/html; charset=UTF-8')->setBody($previewHtml);
    }

    public function save(string $destinationPath): string
    {
        if (Pdf::isFaking()) {
            Pdf::getFake()?->record($this, 'save', destination: $destinationPath);
            return $destinationPath;
        }

        $directory = dirname($destinationPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($destinationPath, $this->output());

        return $destinationPath;
    }

    public function filename(string $name): static
    {
        $this->filename = $name;
        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function inline(?string $filename = null): ResponseInterface
    {
        $filename = $filename ?? $this->filename ?? 'document.pdf';
        if (!str_ends_with(strtolower($filename), '.pdf')) {
            $filename .= '.pdf';
        }

        if (Pdf::isFaking()) {
            Pdf::getFake()?->record($this, 'inline', filename: $filename);
            /** @var ResponseInterface $response */
            $response = service('response');
            return $response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="' . basename($filename) . '"')
                ->setBody("%PDF-1.4 Fake PDF Inline Content\n%%EOF");
        }

        $binary = $this->output();

        /** @var ResponseInterface $response */
        $response = service('response');

        return $response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($filename) . '"')
            ->setHeader('Content-Length', (string) strlen($binary))
            ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
            ->setHeader('Pragma', 'public')
            ->setBody($binary);
    }

    public function download(?string $filename = null): ResponseInterface
    {
        $filename = $filename ?? $this->filename ?? 'document.pdf';
        if (!str_ends_with(strtolower($filename), '.pdf')) {
            $filename .= '.pdf';
        }

        if (Pdf::isFaking()) {
            Pdf::getFake()?->record($this, 'download', filename: $filename);
            /** @var ResponseInterface $response */
            $response = service('response');
            return $response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'attachment; filename="' . basename($filename) . '"')
                ->setBody("%PDF-1.4 Fake PDF Download Content\n%%EOF");
        }

        $binary = $this->output();

        /** @var ResponseInterface $response */
        $response = service('response');

        return $response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . basename($filename) . '"')
            ->setHeader('Content-Length', (string) strlen($binary))
            ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
            ->setHeader('Pragma', 'public')
            ->setBody($binary);
    }

    public function attachTo(mixed $email, ?string $filename = null, string $disposition = 'attachment'): static
    {
        $filename = $filename ?? $this->filename ?? 'document.pdf';
        if (!str_ends_with(strtolower($filename), '.pdf')) {
            $filename .= '.pdf';
        }

        if (Pdf::isFaking()) {
            Pdf::getFake()?->record($this, 'attachTo', filename: $filename);
            return $this;
        }

        if (is_object($email) && method_exists($email, 'attach')) {
            $tempDir = sys_get_temp_dir() . '/jengo-pdf-attachments';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $tempPath = $tempDir . '/' . uniqid('pdf_') . '_' . basename($filename);
            file_put_contents($tempPath, $this->output());
            $email->attach($tempPath, $disposition, basename($filename), 'application/pdf');
        }

        return $this;
    }

    public function store(string $path, ?string $disk = null): string
    {
        if (Pdf::isFaking()) {
            Pdf::getFake()?->record($this, 'store', destination: $path);
            return $path;
        }

        // Integration with jengo/storage or custom disk if available
        if (class_exists('Jengo\Storage\Storage')) {
            $storageClass = 'Jengo\Storage\Storage';
            if ($disk !== null && method_exists($storageClass, 'disk')) {
                $storageClass::disk($disk)->put($path, $this->output());
            } else {
                $storageClass::put($path, $this->output());
            }
            return $path;
        }

        // Fallback: CodeIgniter WRITEPATH or direct file storage
        $fullPath = (defined('WRITEPATH') && !str_starts_with($path, '/'))
            ? WRITEPATH . ltrim($path, '/')
            : $path;

        return $this->save($fullPath);
    }

    // Getters for Drivers
    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function getView(): ?string
    {
        return $this->view;
    }

    public function getViewData(): array
    {
        return $this->viewData;
    }

    public function getTemplateName(): ?string
    {
        return $this->templateName;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getFormat(): ?PaperFormat
    {
        return $this->format;
    }

    public function getOrientation(): ?Orientation
    {
        return $this->orientation;
    }

    public function getMargins(): ?Margins
    {
        return $this->margins;
    }

    public function getScale(): float
    {
        return $this->scale;
    }

    public function hasBackground(): bool
    {
        return $this->background;
    }

    public function getMediaType(): ?MediaType
    {
        return $this->mediaType;
    }

    public function getHeader(): ?HeaderFooter
    {
        return $this->header;
    }

    public function getFooter(): ?HeaderFooter
    {
        return $this->footer;
    }

    public function getWaitForSelector(): ?string
    {
        return $this->waitForSelector;
    }

    public function getWaitForTimeout(): ?int
    {
        return $this->waitForTimeout;
    }
}
