<?php

declare(strict_types=1);

namespace Jengo\Pdf\Jobs;

use Jengo\Pdf\Pdf;
use Jengo\Queues\Traits\Queueable;

class RenderPdfJob
{
    use Queueable;

    /**
     * @param array<string, mixed> $documentOptions
     */
    public function __construct(
        public string $destinationPath,
        public ?string $disk = null,
        public ?string $html = null,
        public ?string $view = null,
        public array $viewData = [],
        public ?string $templateName = null,
        public ?string $url = null,
        public array $documentOptions = []
    ) {
    }

    public function handle(): void
    {
        $document = Pdf::newDocument();

        if ($this->html !== null) {
            $document->html($this->html);
        } elseif ($this->templateName !== null) {
            $document->template($this->templateName, $this->viewData);
        } elseif ($this->view !== null) {
            $document->view($this->view, $this->viewData);
        } elseif ($this->url !== null) {
            $document->url($this->url);
        }

        if (isset($this->documentOptions['format'])) {
            $document->format($this->documentOptions['format']);
        }
        if (isset($this->documentOptions['orientation'])) {
            $document->orientation($this->documentOptions['orientation']);
        }
        if (isset($this->documentOptions['scale'])) {
            $document->scale((float) $this->documentOptions['scale']);
        }
        if (isset($this->documentOptions['driver'])) {
            $document->driver($this->documentOptions['driver']);
        }
        if (isset($this->documentOptions['watermark'])) {
            $document->watermark($this->documentOptions['watermark']);
        }

        $document->store($this->destinationPath, $this->disk);
    }
}
