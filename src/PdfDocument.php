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
use Jengo\Pdf\Filtering\FilterField;
use Jengo\Pdf\Support\HeaderFooter;
use Jengo\Pdf\Support\Margins;
use Jengo\Pdf\Support\Watermark;
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
    protected ?Watermark $watermark = null;

    protected ?HeaderFooter $header = null;
    protected ?HeaderFooter $footer = null;

    protected ?string $waitForSelector = null;
    protected ?int $waitForTimeout = null;

    protected string|DriverInterface|null $driver = null;
    protected ?string $filename = null;
    protected ?string $renderedOutput = null;

    /**
     * @var array<\Jengo\Pdf\Filtering\FilterField>
     */
    protected array $filters = [];

    /**
     * @var callable|null
     */
    protected mixed $filterCallback = null;

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

    public function viewData(array $data, bool $merge = true): static
    {
        $this->viewData = $merge ? array_merge($this->viewData, $data) : $data;
        $this->renderedOutput = null;

        return $this;
    }

    /**
     * Attach filter field definitions for the interactive preview slide-over drawer.
     *
     * @param array<\Jengo\Pdf\Filtering\FilterField> $filters
     */
    public function withFilters(array $filters): static
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Register a callback executed when filters are adjusted in the preview or passed via request.
     * The callback receives: function(array $filters, \Jengo\Pdf\PdfDocument $doc): void|array
     */
    public function onFilter(callable $callback): static
    {
        $this->filterCallback = $callback;
        return $this;
    }

    /**
     * Get the registered filter field definitions.
     *
     * @return array<\Jengo\Pdf\Filtering\FilterField>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Get the registered filter callback.
     */
    public function getFilterCallback(): ?callable
    {
        return $this->filterCallback;
    }

    /**
     * Execute the filter callback with given filter values against this document.
     *
     * @param array<string, mixed> $filters
     */
    public function applyFilters(array $filters): static
    {
        if ($this->filterCallback !== null) {
            $result = ($this->filterCallback)($filters, $this);
            if (is_array($result)) {
                $this->viewData($result);
            }
            $this->renderedOutput = null;
        }

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

        // Merge watermark configuration
        if (isset($data['watermark'])) {
            $this->watermark($data['watermark']);
        } elseif ($this->watermark === null && isset($defaults['watermark']) && is_array($defaults['watermark']) && !empty($defaults['watermark']['enabled'])) {
            $this->watermark($defaults['watermark']);
        }

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

    public function watermark(
        string|bool|array|Watermark $textOrConfig = 'JENGO',
        float $opacity = 0.08,
        ?string $color = null,
        ?int $angle = -35,
        ?string $size = null
    ): static {
        $this->watermark = Watermark::make($textOrConfig, $opacity, $color, $angle, $size);
        $this->renderedOutput = null;

        return $this;
    }

    public function getWatermark(): ?Watermark
    {
        return $this->watermark;
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
        $html = '';
        if ($this->html !== null) {
            $html = $this->html;
        } elseif ($this->view !== null) {
            $html = view($this->view, $this->viewData);
        }

        $watermark = $this->watermark;
        if ($watermark === null && isset($this->viewData['watermark'])) {
            $watermark = Watermark::make($this->viewData['watermark']);
        }

        if ($watermark !== null && $watermark->enabled) {
            $watermarkHtml = $watermark->renderHtml();
            if (stripos($html, '</body>') !== false) {
                $html = str_ireplace('</body>', $watermarkHtml . '</body>', $html);
            } else {
                $html .= $watermarkHtml;
            }
        }

        return $html;
    }

    public function preview(bool $withToolbar = true): ResponseInterface
    {
        $request = service('request');
        $isFilterRequest = ($request->getHeaderLine('X-Jengo-Pdf-Filter') === '1')
            || ($request->getGet('jengo_pdf_filter') === '1')
            || ($request->getPost('jengo_pdf_filter') === '1');

        if ($isFilterRequest && $this->filterCallback !== null) {
            $json = $request->getJSON(true);
            $post = $request->getPost() ?: [];
            $get = $request->getGet() ?: [];

            $submitted = array_merge($get, $post, is_array($json) ? $json : []);
            unset($submitted['jengo_pdf_filter']);

            $this->applyFilters($submitted);
            $newHtml = $this->toHtml();

            /** @var ResponseInterface $response */
            $response = service('response');
            return $response->setJSON([
                'status'      => 'success',
                'html'        => $newHtml,
                'format'      => $this->format?->value ?? 'A4',
                'orientation' => $this->orientation?->value ?? 'portrait',
                'isLandscape' => $this->orientation?->isLandscape() ?? false,
                'filters'     => $submitted,
            ]);
        }

        // Apply initial filter defaults or active query parameters
        $initialFilters = [];
        if (!empty($this->filters)) {
            foreach ($this->filters as $filter) {
                if ($filter instanceof FilterField) {
                    $val = $request->getGet($filter->name);
                    if ($val !== null) {
                        $initialFilters[$filter->name] = $val;
                    } elseif ($filter->default !== null) {
                        $initialFilters[$filter->name] = $filter->default;
                    }
                }
            }
            if (!empty($initialFilters)) {
                $this->applyFilters($initialFilters);
            }
        }

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
        $isLandscapeJs = $isLandscape ? 'true' : 'false';
        $widthMm = $isLandscape ? '297mm' : '210mm';
        $heightMm = $isLandscape ? '210mm' : '297mm';

        $hasFilters = !empty($this->filters);
        $filterFieldsHtml = '';
        if ($hasFilters) {
            foreach ($this->filters as $filter) {
                if ($filter instanceof FilterField) {
                    $currVal = $initialFilters[$filter->name] ?? null;
                    $filterFieldsHtml .= $filter->renderHtml($currVal);
                }
            }
        }

        $filterButtonHtml = $hasFilters ? '<button onclick="jengoToggleFilters()" class="jengo-btn jengo-btn-secondary" id="jengoFilterToggleBtn" title="Toggle Filters (F)">Filters <span id="jengoFilterBadge" class="jengo-filter-count-badge" style="display: none;">0</span></button>' : '';

        $filterDrawerHtml = $hasFilters ? <<<HTML
    <!-- Slide-Over Filter Drawer Backdrop -->
    <div class="jengo-drawer-backdrop" id="jengoDrawerBackdrop" onclick="jengoCloseFilters()"></div>

    <!-- Slide-Over Filter Drawer -->
    <div class="jengo-filter-drawer" id="jengoFilterDrawer">
        <div class="jengo-drawer-header">
            <div>
                <div style="font-weight: 700; font-size: 14px; color: #f8fafc; display: flex; align-items: center; gap: 8px;">
                    <span>Document Filters</span>
                    <span class="jengo-badge" id="jengoFilterLiveBadge" style="font-size: 10px; color: #10b981; border-color: #065f46;">Live</span>
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Changes auto-update the PDF canvas</div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button onclick="jengoResetFilters()" class="jengo-btn jengo-btn-secondary" style="padding: 4px 8px; font-size: 11px;">Reset</button>
                <button onclick="jengoCloseFilters()" class="jengo-drawer-close-btn" title="Close Drawer (Esc)">&times;</button>
            </div>
        </div>

        <div class="jengo-drawer-body">
            <form id="jengoFilterForm" onsubmit="event.preventDefault();">
                {$filterFieldsHtml}
            </form>
        </div>

        <div class="jengo-drawer-footer">
            <div id="jengoFilterStatus" style="font-size: 11px; color: #94a3b8; display: flex; align-items: center; gap: 6px;">
                <span class="jengo-status-dot"></span> Ready
            </div>
            <button onclick="jengoApplyFiltersNow()" class="jengo-btn" style="padding: 6px 12px;">Apply</button>
        </div>
    </div>
HTML : '';

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
            overflow-x: hidden;
        }
        .jengo-preview-toolbar {
            position: sticky;
            top: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 24px;
            background: rgba(15, 23, 42, 0.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #334155;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
            flex-wrap: wrap;
            gap: 12px;
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
        .jengo-page-nav {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 12px;
            color: #f1f5f9;
        }
        .jengo-page-nav button {
            background: transparent;
            border: none;
            color: #38bdf8;
            cursor: pointer;
            padding: 2px 4px;
            font-size: 11px;
            font-weight: bold;
            border-radius: 4px;
        }
        .jengo-page-nav button:hover { background: #334155; }
        .jengo-toolbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .jengo-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
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
        .jengo-btn-success {
            background: #059669;
            color: #ffffff;
        }
        .jengo-btn-success:hover { background: #047857; }
        .jengo-preview-canvas {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px 20px 80px;
            background-color: #0b1120;
            min-height: calc(100vh - 60px);
            overflow-x: auto;
            gap: 32px;
            transition: transform 0.2s ease-out;
        }
        .jengo-sheet-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 32px;
            transform-origin: top center;
            transition: transform 0.2s ease-out;
        }
        .jengo-sheet-frame {
            width: {$widthMm};
            height: {$heightMm};
            min-height: {$heightMm};
            max-height: {$heightMm};
            background: #ffffff;
            color: #1e293b;
            padding: 3mm 4mm 4mm;
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .jengo-sheet-frame .jengo-watermark {
            position: absolute !important;
            top: 50% !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            transform: translateY(-50%) rotate(-35deg) !important;
            pointer-events: none !important;
            z-index: 0 !important;
        }
        .jengo-sheet-content {
            flex: 1 1 auto;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }
        .jengo-sheet-number {
            font-size: 8pt;
            color: #94a3b8;
            text-align: right;
            border-top: 1px solid #f1f5f9;
            padding-top: 4px;
            margin-top: 4px;
            flex-shrink: 0;
            position: relative;
            z-index: 1;
        }
        .jengo-continuous .jengo-sheet-frame {
            height: auto !important;
            min-height: {$heightMm} !important;
            max-height: none !important;
            overflow: visible !important;
        }
        .jengo-continuous .jengo-sheet-content {
            height: auto !important;
            overflow: visible !important;
        }

        /* Slide-Over Filter Drawer Styles */
        .jengo-drawer-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(2px);
            z-index: 99998;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }
        .jengo-drawer-backdrop.active {
            opacity: 1;
            pointer-events: auto;
        }
        .jengo-filter-drawer {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 360px;
            max-width: 90vw;
            background: #0f172a;
            border-left: 1px solid #334155;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.6);
            z-index: 99999;
            display: flex;
            flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .jengo-filter-drawer.open {
            transform: translateX(0);
        }
        .jengo-drawer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid #1e293b;
            background: #0b1120;
        }
        .jengo-drawer-close-btn {
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 20px;
            line-height: 1;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .jengo-drawer-close-btn:hover {
            background: #1e293b;
            color: #f1f5f9;
        }
        .jengo-drawer-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .jengo-drawer-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            border-top: 1px solid #1e293b;
            background: #0b1120;
        }
        .jengo-filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }
        .jengo-filter-label {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .jengo-filter-input, .jengo-filter-select {
            width: 100%;
            padding: 8px 12px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 6px;
            color: #f1f5f9;
            font-size: 13px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }
        .jengo-filter-input:focus, .jengo-filter-select:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
        }
        .jengo-filter-range-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .jengo-toggle-switch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            user-select: none;
            padding: 4px 0;
        }
        .jengo-toggle-input {
            appearance: none;
            width: 36px;
            height: 20px;
            background: #334155;
            border-radius: 10px;
            position: relative;
            outline: none;
            cursor: pointer;
            transition: background 0.2s;
            margin: 0;
        }
        .jengo-toggle-input:checked {
            background: #0284c7;
        }
        .jengo-toggle-input::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px;
            height: 16px;
            background: #ffffff;
            border-radius: 50%;
            transition: transform 0.2s;
        }
        .jengo-toggle-input:checked::after {
            transform: translateX(16px);
        }
        .jengo-filter-count-badge {
            background: #0284c7;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 9999px;
            margin-left: 4px;
        }
        .jengo-btn-active {
            background: #0284c7 !important;
            color: #ffffff !important;
        }
        .jengo-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            display: inline-block;
        }
        .jengo-status-dot.loading {
            background: #f59e0b;
            animation: jengoPulse 1s infinite;
        }
        @keyframes jengoPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        @media print {
            .jengo-preview-toolbar, .jengo-drawer-backdrop, .jengo-filter-drawer { display: none !important; }
            body { background: transparent !important; }
            .jengo-preview-canvas { padding: 0 !important; background: transparent !important; gap: 0 !important; }
            .jengo-sheet-container { gap: 0 !important; transform: none !important; }
            .jengo-sheet-frame {
                box-shadow: none !important;
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 3mm 4mm !important;
                page-break-after: always !important;
                break-after: page !important;
                border-radius: 0 !important;
            }
            .jengo-sheet-number { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="jengo-preview-toolbar">
        <div class="jengo-toolbar-left">
            <div style="font-weight: 800; font-size: 14px; color: #ffffff; display: flex; align-items: center; gap: 6px;">
                <span style="color: #38bdf8;">Jengo</span>PDF Preview
            </div>
            <div class="jengo-badge">{$format} &bull; {$orientation}</div>
            <div class="jengo-page-nav" id="jengoPageNav">
                <button onclick="jengoPrevPage()" title="Previous Page">Prev</button>
                <span>Page <strong id="jengoCurrentPage">1</strong> of <strong id="jengoTotalPages">1</strong></span>
                <button onclick="jengoNextPage()" title="Next Page">Next</button>
            </div>
        </div>
        <div class="jengo-toolbar-actions">
            {$filterButtonHtml}
            <button onclick="jengoZoom(-0.1)" class="jengo-btn jengo-btn-secondary" title="Zoom Out">Zoom -</button>
            <button onclick="jengoZoom(0)" class="jengo-btn jengo-btn-secondary" id="jengoZoomLabel">100%</button>
            <button onclick="jengoZoom(0.1)" class="jengo-btn jengo-btn-secondary" title="Zoom In">Zoom +</button>
            <button onclick="jengoToggleFit()" class="jengo-btn jengo-btn-secondary" id="jengoFitBtn">Fit Width</button>
            <button onclick="jengoToggleViewMode()" class="jengo-btn jengo-btn-secondary" id="jengoViewModeBtn">Continuous</button>
            <button onclick="window.print()" class="jengo-btn jengo-btn-secondary">Print</button>
            <button onclick="jengoDownloadPdf()" class="jengo-btn jengo-btn-success">Download PDF</button>
        </div>
    </div>

    {$filterDrawerHtml}

    <!-- Hidden Persistent Storage of Raw Unmodified Document HTML -->
    <div id="jengoSourceStorage" style="display: none !important;">{$rawHtml}</div>

    <!-- Hidden Calibration Ruler for Exact Physical MM to PX Conversion -->
    <div id="jengoRuler" style="width: 100mm; height: 100mm; position: absolute; visibility: hidden; pointer-events: none;"></div>

    <div class="jengo-preview-canvas" id="jengoCanvas">
        <div class="jengo-sheet-container" id="jengoSheetContainer">
            <!-- Initial sheet frame -->
            <div class="jengo-sheet-frame" id="jengo-sheet-1">
                <div class="jengo-sheet-content">
                    {$rawHtml}
                </div>
            </div>
        </div>
    </div>

    <script>
        let jengoCurrentZoom = 1.0;
        let jengoIsFitWidth = false;
        let jengoIsPaginated = true;
        let jengoTotalPagesCount = 1;
        let jengoActivePage = 1;
        let jengoDrawerOpen = false;
        let jengoFilterDebounceTimer = null;
        let jengoAbortController = null;

        function getMmToPxRatio() {
            const ruler = document.getElementById('jengoRuler');
            return (ruler && ruler.offsetWidth) ? (ruler.offsetWidth / 100) : 3.7795;
        }

        function getSourceContent() {
            const storage = document.getElementById('jengoSourceStorage');
            if (storage) {
                return storage.cloneNode(true);
            }
            return null;
        }

        function paginatePreview() {
            const container = document.getElementById('jengoSheetContainer');
            const sourceContent = getSourceContent();
            if (!container || !sourceContent) return;

            const watermarkEl = sourceContent.querySelector('.jengo-watermark');

            const isLandscape = {$isLandscapeJs};
            const widthMm = isLandscape ? 297 : 210;
            const heightMm = isLandscape ? 210 : 297;
            const paddingMm = 7; // 3mm top + 4mm bottom
            const ratio = getMmToPxRatio();

            const printableHeightPx = (heightMm - paddingMm) * ratio - 14;

            // Look for table-based documents (e.g. Schema Reports, itemized invoices)
            const allTables = Array.from(sourceContent.querySelectorAll('table'));
            let mainTable = null;
            let maxRows = 0;

            for (const tbl of allTables) {
                const tbody = tbl.querySelector('tbody');
                const rowCount = tbody ? tbody.rows.length : tbl.rows.length;
                if (rowCount > maxRows) {
                    maxRows = rowCount;
                    mainTable = tbl;
                }
            }

            const tableBody = mainTable ? mainTable.querySelector('tbody') : null;

            if (mainTable && tableBody && tableBody.rows.length > 5) {
                const tableRows = Array.from(tableBody.rows);
                const thead = mainTable.querySelector('thead');
                const tfoot = mainTable.querySelector('tfoot');

                let topLevelMainNode = mainTable;
                while (topLevelMainNode.parentElement && topLevelMainNode.parentElement !== sourceContent) {
                    topLevelMainNode = topLevelMainNode.parentElement;
                }

                const preElements = [];
                let curr = sourceContent.firstElementChild;
                while (curr && curr !== topLevelMainNode) {
                    if (!curr.classList.contains('jengo-watermark')) {
                        preElements.push(curr.cloneNode(true));
                    }
                    curr = curr.nextElementSibling;
                }

                const postElements = [];
                curr = topLevelMainNode.nextElementSibling;
                while (curr) {
                    if (!curr.classList.contains('jengo-watermark')) {
                        postElements.push(curr.cloneNode(true));
                    }
                    curr = curr.nextElementSibling;
                }

                container.innerHTML = '';
                let pageIndex = 1;
                let currentRowIndex = 0;

                while (currentRowIndex < tableRows.length) {
                    const pageFrame = document.createElement('div');
                    pageFrame.className = 'jengo-sheet-frame';
                    pageFrame.id = 'jengo-sheet-' + pageIndex;

                    if (watermarkEl) {
                        pageFrame.appendChild(watermarkEl.cloneNode(true));
                    }

                    const pageContent = document.createElement('div');
                    pageContent.className = 'jengo-sheet-content';

                    if (pageIndex === 1) {
                        preElements.forEach(el => pageContent.appendChild(el.cloneNode(true)));
                    }

                    const pageTable = mainTable.cloneNode(false);
                    if (thead) {
                        pageTable.appendChild(thead.cloneNode(true));
                    }

                    const pageTbody = document.createElement('tbody');
                    pageTable.appendChild(pageTbody);
                    pageContent.appendChild(pageTable);
                    pageFrame.appendChild(pageContent);

                    const pageNumberEl = document.createElement('div');
                    pageNumberEl.className = 'jengo-sheet-number';
                    pageNumberEl.innerHTML = 'Page ' + pageIndex;
                    pageFrame.appendChild(pageNumberEl);

                    container.appendChild(pageFrame);

                    while (currentRowIndex < tableRows.length) {
                        const rowClone = tableRows[currentRowIndex].cloneNode(true);
                        pageTbody.appendChild(rowClone);

                        if (pageContent.scrollHeight > printableHeightPx && pageTbody.rows.length > 1) {
                            pageTbody.removeChild(rowClone);
                            break;
                        }
                        currentRowIndex++;
                    }

                    if (currentRowIndex >= tableRows.length) {
                        if (tfoot) {
                            pageTable.appendChild(tfoot.cloneNode(true));
                        }
                        postElements.forEach(el => pageContent.appendChild(el.cloneNode(true)));
                    }

                    pageIndex++;
                }

                jengoTotalPagesCount = pageIndex - 1;
            } else {
                jengoTotalPagesCount = 1;
                container.innerHTML = '';
                const pageFrame = document.createElement('div');
                pageFrame.className = 'jengo-sheet-frame';
                pageFrame.id = 'jengo-sheet-1';

                if (watermarkEl) {
                    pageFrame.appendChild(watermarkEl.cloneNode(true));
                }

                const pageContent = document.createElement('div');
                pageContent.className = 'jengo-sheet-content';
                const clonedContent = sourceContent.cloneNode(true);
                const wmInside = clonedContent.querySelector('.jengo-watermark');
                if (wmInside) wmInside.remove();
                pageContent.innerHTML = clonedContent.innerHTML;
                pageFrame.appendChild(pageContent);

                const pageNumberEl = document.createElement('div');
                pageNumberEl.className = 'jengo-sheet-number';
                pageNumberEl.innerHTML = 'Page 1 of 1';
                pageFrame.appendChild(pageNumberEl);

                container.appendChild(pageFrame);
            }

            const allSheets = container.querySelectorAll('.jengo-sheet-frame');
            allSheets.forEach((sheet, idx) => {
                const numEl = sheet.querySelector('.jengo-sheet-number');
                if (numEl) {
                    numEl.innerHTML = 'Page ' + (idx + 1) + ' of ' + jengoTotalPagesCount;
                }
            });

            document.getElementById('jengoTotalPages').textContent = jengoTotalPagesCount;
            updateActivePageOnScroll();
        }

        function updateActivePageOnScroll() {
            const container = document.getElementById('jengoSheetContainer');
            if (!container) return;
            const sheets = container.querySelectorAll('.jengo-sheet-frame');
            let current = 1;
            const scrollPos = window.scrollY + 120;

            sheets.forEach((sheet, idx) => {
                if (sheet.offsetTop <= scrollPos) {
                    current = idx + 1;
                }
            });

            jengoActivePage = current;
            const pageBadge = document.getElementById('jengoCurrentPage');
            if (pageBadge) pageBadge.textContent = current;
        }

        function jengoPrevPage() {
            if (jengoActivePage > 1) {
                jengoActivePage--;
                const target = document.getElementById('jengo-sheet-' + jengoActivePage);
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function jengoNextPage() {
            if (jengoActivePage < jengoTotalPagesCount) {
                jengoActivePage++;
                const target = document.getElementById('jengo-sheet-' + jengoActivePage);
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function jengoZoom(delta) {
            if (delta === 0) {
                jengoCurrentZoom = 1.0;
            } else {
                jengoCurrentZoom = Math.max(0.4, Math.min(2.0, jengoCurrentZoom + delta));
            }
            jengoIsFitWidth = false;
            applyZoom();
        }

        function jengoToggleFit() {
            jengoIsFitWidth = !jengoIsFitWidth;
            if (jengoIsFitWidth) {
                const container = document.getElementById('jengoSheetContainer');
                const firstSheet = container ? container.querySelector('.jengo-sheet-frame') : null;
                if (firstSheet) {
                    const canvasWidth = document.getElementById('jengoCanvas').clientWidth - 60;
                    const sheetWidth = firstSheet.offsetWidth;
                    jengoCurrentZoom = Math.min(1.5, Math.max(0.5, canvasWidth / sheetWidth));
                }
            } else {
                jengoCurrentZoom = 1.0;
            }
            applyZoom();
        }

        function applyZoom() {
            const container = document.getElementById('jengoSheetContainer');
            if (container) {
                container.style.transform = 'scale(' + jengoCurrentZoom.toFixed(2) + ')';
            }
            const label = document.getElementById('jengoZoomLabel');
            if (label) {
                label.textContent = Math.round(jengoCurrentZoom * 100) + '%';
            }
            const fitBtn = document.getElementById('jengoFitBtn');
            if (fitBtn) {
                fitBtn.style.background = jengoIsFitWidth ? '#0284c7' : '#334155';
            }
        }

        function jengoToggleViewMode() {
            const canvas = document.getElementById('jengoCanvas');
            jengoIsPaginated = !jengoIsPaginated;
            const container = document.getElementById('jengoSheetContainer');
            const storage = document.getElementById('jengoSourceStorage');

            if (jengoIsPaginated) {
                canvas.classList.remove('jengo-continuous');
                document.getElementById('jengoViewModeBtn').textContent = 'Continuous';
                document.getElementById('jengoPageNav').style.display = 'inline-flex';
                paginatePreview();
            } else {
                canvas.classList.add('jengo-continuous');
                document.getElementById('jengoViewModeBtn').textContent = 'Paginated';
                document.getElementById('jengoPageNav').style.display = 'none';
                if (container && storage) {
                    const clonedStorage = storage.cloneNode(true);
                    const wm = clonedStorage.querySelector('.jengo-watermark');
                    let wmHtml = '';
                    if (wm) {
                        wmHtml = wm.outerHTML;
                        wm.remove();
                    }
                    container.innerHTML = '<div class="jengo-sheet-frame" id="jengo-sheet-1">' + wmHtml + '<div class="jengo-sheet-content">' + clonedStorage.innerHTML + '</div></div>';
                }
            }
        }

        function jengoToggleFilters() {
            if (jengoDrawerOpen) {
                jengoCloseFilters();
            } else {
                jengoOpenFilters();
            }
        }

        function jengoOpenFilters() {
            const drawer = document.getElementById('jengoFilterDrawer');
            const backdrop = document.getElementById('jengoDrawerBackdrop');
            if (drawer && backdrop) {
                drawer.classList.add('open');
                backdrop.classList.add('active');
                jengoDrawerOpen = true;
                const firstInput = drawer.querySelector('input, select');
                if (firstInput) firstInput.focus();
            }
        }

        function jengoCloseFilters() {
            const drawer = document.getElementById('jengoFilterDrawer');
            const backdrop = document.getElementById('jengoDrawerBackdrop');
            if (drawer && backdrop) {
                drawer.classList.remove('open');
                backdrop.classList.remove('active');
                jengoDrawerOpen = false;
            }
        }

        function jengoGetFilterFormData() {
            const form = document.getElementById('jengoFilterForm');
            if (!form) return {};
            const formData = new FormData(form);
            const data = {};

            for (const [key, val] of formData.entries()) {
                if (key.includes('[') && key.endsWith(']')) {
                    const parts = key.split('[');
                    const rootKey = parts[0];
                    const subKey = parts[1].replace(']', '');
                    if (!data[rootKey]) data[rootKey] = {};
                    data[rootKey][subKey] = val;
                } else {
                    data[key] = val;
                }
            }

            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(cb => {
                if (!cb.checked && !data[cb.name]) {
                    data[cb.name] = 0;
                }
            });

            return data;
        }

        function jengoUpdateFilterBadge() {
            const data = jengoGetFilterFormData();
            let count = 0;
            for (const k in data) {
                const v = data[k];
                if (typeof v === 'object' && v !== null) {
                    if (Object.values(v).some(x => x !== '')) count++;
                } else if (v !== '' && v !== '0' && v !== 0 && v !== false && v !== null && v !== 'all') {
                    count++;
                }
            }
            const badge = document.getElementById('jengoFilterBadge');
            const toggleBtn = document.getElementById('jengoFilterToggleBtn');
            if (badge) {
                if (count > 0) {
                    badge.textContent = count;
                    badge.style.display = 'inline-block';
                    if (toggleBtn) toggleBtn.classList.add('jengo-btn-active');
                } else {
                    badge.style.display = 'none';
                    if (toggleBtn) toggleBtn.classList.remove('jengo-btn-active');
                }
            }
        }

        function jengoDebounceFilterChange() {
            jengoUpdateFilterBadge();
            clearTimeout(jengoFilterDebounceTimer);
            jengoSetStatus('Updating preview...', true);

            jengoFilterDebounceTimer = setTimeout(() => {
                jengoApplyFiltersNow();
            }, 300);
        }

        function jengoSetStatus(text, isLoading = false) {
            const statusEl = document.getElementById('jengoFilterStatus');
            if (statusEl) {
                statusEl.innerHTML = '<span class="jengo-status-dot ' + (isLoading ? 'loading' : '') + '"></span> ' + text;
            }
        }

        function jengoApplyFiltersNow() {
            const filterData = jengoGetFilterFormData();
            if (jengoAbortController) {
                jengoAbortController.abort();
            }
            jengoAbortController = new AbortController();

            jengoSetStatus('Updating canvas...', true);

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Jengo-Pdf-Filter': '1',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(filterData),
                signal: jengoAbortController.signal
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP error ' + res.status);
                return res.json();
            })
            .then(data => {
                if (data.status === 'success' && data.html) {
                    const storage = document.getElementById('jengoSourceStorage');
                    if (storage) {
                        storage.innerHTML = data.html;
                        paginatePreview();
                    }
                    jengoSetStatus('Ready', false);
                    jengoSyncUrl(filterData);
                }
            })
            .catch(err => {
                if (err.name !== 'AbortError') {
                    jengoSetStatus('Error updating preview', false);
                }
            });
        }

        function jengoResetFilters() {
            const form = document.getElementById('jengoFilterForm');
            if (form) {
                form.reset();
                jengoDebounceFilterChange();
            }
        }

        function jengoSyncUrl(filters) {
            try {
                const url = new URL(window.location.href);
                for (const [k, v] of Object.entries(filters)) {
                    if (typeof v === 'object' && v !== null) {
                        for (const [subK, subV] of Object.entries(v)) {
                            if (subV !== '') {
                                url.searchParams.set(k + '[' + subK + ']', subV);
                            } else {
                                url.searchParams.delete(k + '[' + subK + ']');
                            }
                        }
                    } else if (v !== '' && v !== null && v !== undefined) {
                        url.searchParams.set(k, v);
                    } else {
                        url.searchParams.delete(k);
                    }
                }
                window.history.replaceState(null, '', url.toString());
            } catch (e) {}
        }

        function jengoDownloadPdf() {
            let currentUrl = window.location.href;
            let downloadUrl;
            if (currentUrl.includes('/preview')) {
                downloadUrl = currentUrl.replace('/preview', '/download');
            } else if (currentUrl.includes('action=preview')) {
                downloadUrl = currentUrl.replace('action=preview', 'action=download');
            } else {
                const url = new URL(currentUrl);
                url.searchParams.set('action', 'download');
                downloadUrl = url.toString();
            }
            window.location.href = downloadUrl;
        }

        window.addEventListener('DOMContentLoaded', () => {
            paginatePreview();
            window.addEventListener('scroll', updateActivePageOnScroll, { passive: true });

            const form = document.getElementById('jengoFilterForm');
            if (form) {
                form.addEventListener('input', jengoDebounceFilterChange);
                form.addEventListener('change', jengoDebounceFilterChange);
                jengoUpdateFilterBadge();
            }
        });

        window.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
                if (e.key === 'Escape') {
                    jengoCloseFilters();
                }
                return;
            }
            if (e.key === 'Escape') {
                jengoCloseFilters();
            } else if (e.key === 'f' || e.key === 'F') {
                e.preventDefault();
                jengoToggleFilters();
            } else if (e.key === 'ArrowUp' || e.key === 'PageUp') {
                jengoPrevPage();
            } else if (e.key === 'ArrowDown' || e.key === 'PageDown') {
                jengoNextPage();
            } else if (e.key === 'd' || e.key === 'D') {
                jengoDownloadPdf();
            } else if (e.key === '+' || e.key === '=') {
                jengoZoom(0.1);
            } else if (e.key === '-' || e.key === '_') {
                jengoZoom(-0.1);
            } else if (e.key === '0') {
                jengoZoom(0);
            }
        });
    </script>
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

        if ($this->filterCallback !== null) {
            $req = service('request');
            $activeFilters = [];
            foreach ($this->filters as $f) {
                if ($f instanceof FilterField && ($v = $req->getGet($f->name)) !== null) {
                    $activeFilters[$f->name] = $v;
                }
            }
            if (!empty($activeFilters)) {
                $this->applyFilters($activeFilters);
            }
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

        if ($this->filterCallback !== null) {
            $req = service('request');
            $activeFilters = [];
            foreach ($this->filters as $f) {
                if ($f instanceof FilterField && ($v = $req->getGet($f->name)) !== null) {
                    $activeFilters[$f->name] = $v;
                }
            }
            if (!empty($activeFilters)) {
                $this->applyFilters($activeFilters);
            }
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
