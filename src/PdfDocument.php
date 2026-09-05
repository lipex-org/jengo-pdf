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

class PdfDocument implements PdfInterface
{
    protected ?string $html = null;
    protected ?string $view = null;
    protected array $viewData = [];
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

    public function url(string $url): static
    {
        $this->url = $url;
        $this->html = null;
        $this->view = null;
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
        $this->footer = HeaderFooter::pageNumbers($format);
        $this->renderedOutput = null;

        return $this;
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
        if ($this->renderedOutput !== null) {
            return $this->renderedOutput;
        }

        $driver = $this->resolveDriver();
        $this->renderedOutput = $driver->render($this);

        return $this->renderedOutput;
    }

    public function output(): string
    {
        return $this->render();
    }

    public function base64(): string
    {
        return base64_encode($this->output());
    }

    public function save(string $destinationPath): string
    {
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
