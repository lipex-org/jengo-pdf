<?php

declare(strict_types=1);

namespace Jengo\Pdf\Documents;

use CodeIgniter\HTTP\ResponseInterface;
use Jengo\Pdf\Contracts\DriverInterface;
use Jengo\Pdf\Contracts\PdfInterface;
use Jengo\Pdf\Enums\MediaType;
use Jengo\Pdf\Enums\Orientation;
use Jengo\Pdf\Enums\PaperFormat;
use Jengo\Pdf\Pdf;
use Jengo\Pdf\PdfDocument;
use Jengo\Pdf\Support\Margins;

abstract class AbstractDocumentBuilder implements PdfInterface
{
    protected PdfDocument $document;
    protected array $data = [];

    public function __construct(?PdfDocument $document = null)
    {
        $this->document = $document ?? Pdf::newDocument();
    }

    /**
     * Get the template view alias registered in Config\Pdf::$templating['views'].
     */
    abstract public function getTemplateName(): string;

    /**
     * Compile builder properties into the template data array.
     */
    abstract public function toDataArray(): array;

    /**
     * Synchronize builder data into the underlying PdfDocument before rendering.
     */
    protected function prepareDocument(): PdfDocument
    {
        return $this->document->template($this->getTemplateName(), $this->toDataArray());
    }

    // Common fluent brand / styling / formatting overrides

    public function primaryColor(string $color): static
    {
        $this->data['primaryColor'] = $color;
        return $this;
    }

    public function fontFamily(string $fontFamily): static
    {
        $this->data['fontFamily'] = $fontFamily;
        return $this;
    }

    public function currency(string $currency): static
    {
        $this->data['currency'] = $currency;
        return $this;
    }

    public function dateFormat(string $format): static
    {
        $this->data['dateFormat'] = $format;
        return $this;
    }

    public function footerText(string $text): static
    {
        $this->data['footerText'] = $text;
        return $this;
    }

    public function showPoweredBy(bool $show = true): static
    {
        $this->data['showPoweredBy'] = $show;
        return $this;
    }

    public function company(
        string $name,
        ?string $tagline = null,
        ?string $address = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $taxId = null,
        ?string $logo = null
    ): static {
        $this->data['company'] = array_filter([
            'name'       => $name,
            'tagline'    => $tagline,
            'address'    => $address,
            'email'      => $email,
            'phone'      => $phone,
            'tax_id'     => $taxId,
            'logo'       => $logo,
        ], fn($v) => $v !== null);

        return $this;
    }

    // PdfInterface Implementation Proxies

    public function driver(string|DriverInterface $driver): static
    {
        $this->document->driver($driver);
        return $this;
    }

    public function html(string $html): static
    {
        $this->document->html($html);
        return $this;
    }

    public function view(string $view, array $data = []): static
    {
        $this->document->view($view, $data);
        return $this;
    }

    public function template(string $name, array $data = []): static
    {
        $this->document->template($name, $data);
        return $this;
    }

    public function url(string $url): static
    {
        $this->document->url($url);
        return $this;
    }

    public function format(PaperFormat|string $format): static
    {
        $this->document->format($format);
        return $this;
    }

    public function orientation(Orientation|string $orientation): static
    {
        $this->document->orientation($orientation);
        return $this;
    }

    public function portrait(): static
    {
        $this->document->portrait();
        return $this;
    }

    public function landscape(): static
    {
        $this->document->landscape();
        return $this;
    }

    public function margins(float|Margins $top = 10.0, float $right = 10.0, float $bottom = 10.0, float $left = 10.0, string $unit = 'mm'): static
    {
        $this->document->margins($top, $right, $bottom, $left, $unit);
        return $this;
    }

    public function scale(float $scale): static
    {
        $this->document->scale($scale);
        return $this;
    }

    public function background(bool $showBackground = true): static
    {
        $this->document->background($showBackground);
        return $this;
    }

    public function watermark(
        string|bool|array $textOrConfig = 'JENGO',
        float $opacity = 0.08,
        ?string $color = null,
        ?int $angle = -35,
        ?string $size = null
    ): static {
        $this->document->watermark($textOrConfig, $opacity, $color, $angle, $size);
        return $this;
    }

    public function emulateMedia(MediaType|string $media): static
    {
        $this->document->emulateMedia($media);
        return $this;
    }

    public function header(string $html, float $height = 15.0, string $unit = 'mm'): static
    {
        $this->document->header($html, $height, $unit);
        return $this;
    }

    public function footer(string $html, float $height = 15.0, string $unit = 'mm'): static
    {
        $this->document->footer($html, $height, $unit);
        return $this;
    }

    public function headerView(string $view, array $data = [], float $height = 15.0, string $unit = 'mm'): static
    {
        $this->document->headerView($view, $data, $height, $unit);
        return $this;
    }

    public function footerView(string $view, array $data = [], float $height = 15.0, string $unit = 'mm'): static
    {
        $this->document->footerView($view, $data, $height, $unit);
        return $this;
    }

    public function pageNumbers(string $format = '{page} / {pages}'): static
    {
        $this->document->pageNumbers($format);
        return $this;
    }

    public function filename(string $name): static
    {
        $this->document->filename($name);
        return $this;
    }

    public function output(): string
    {
        return $this->prepareDocument()->output();
    }

    public function base64(): string
    {
        return $this->prepareDocument()->base64();
    }

    public function dataUri(): string
    {
        return $this->prepareDocument()->dataUri();
    }

    public function toHtml(): string
    {
        return $this->prepareDocument()->toHtml();
    }

    public function preview(bool $withToolbar = true): ResponseInterface
    {
        return $this->prepareDocument()->preview($withToolbar);
    }

    public function save(string $destinationPath): string
    {
        return $this->prepareDocument()->save($destinationPath);
    }

    public function inline(?string $filename = null): ResponseInterface
    {
        return $this->prepareDocument()->inline($filename);
    }

    public function download(?string $filename = null): ResponseInterface
    {
        return $this->prepareDocument()->download($filename);
    }

    public function attachTo(mixed $email, ?string $filename = null, string $disposition = 'attachment'): static
    {
        $this->prepareDocument()->attachTo($email, $filename, $disposition);
        return $this;
    }

    public function store(string $path, ?string $disk = null): string
    {
        return $this->prepareDocument()->store($path, $disk);
    }
}
