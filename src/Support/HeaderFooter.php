<?php

declare(strict_types=1);

namespace Jengo\Pdf\Support;

class HeaderFooter
{
    public function __construct(
        public readonly ?string $html = null,
        public readonly ?string $view = null,
        public readonly array $viewData = [],
        public readonly float $height = 15.0,
        public readonly string $heightUnit = 'mm',
        public readonly bool $showPageNumbers = false,
        public readonly string $pageNumberFormat = '{page} / {pages}'
    ) {
    }

    public static function fromHtml(string $html, float $height = 15.0, string $unit = 'mm'): self
    {
        return new self(html: $html, height: $height, heightUnit: $unit);
    }

    public static function fromView(string $view, array $data = [], float $height = 15.0, string $unit = 'mm'): self
    {
        return new self(view: $view, viewData: $data, height: $height, heightUnit: $unit);
    }

    public static function pageNumbers(string $format = '{page} / {pages}', float $height = 15.0, string $unit = 'mm'): self
    {
        return new self(showPageNumbers: true, pageNumberFormat: $format, height: $height, heightUnit: $unit);
    }

    public function renderHtml(): string
    {
        if ($this->html !== null) {
            return $this->html;
        }

        if ($this->view !== null) {
            return view($this->view, $this->viewData);
        }

        if ($this->showPageNumbers) {
            return sprintf(
                '<div style="width: 100%%; text-align: right; font-size: 9pt; color: #718096;">%s</div>',
                htmlspecialchars($this->pageNumberFormat, ENT_QUOTES, 'UTF-8')
            );
        }

        return '';
    }
}
