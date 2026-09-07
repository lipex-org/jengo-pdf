<?php

declare(strict_types=1);

namespace Jengo\Pdf\Testing\Concerns;

use Jengo\Pdf\Pdf;
use Jengo\Pdf\Testing\PdfFake;

trait PdfTestAssertionsTrait
{
    protected function pdfFake(): PdfFake
    {
        return Pdf::getFake() ?? Pdf::fake();
    }

    protected function assertPdfRendered(string|callable $viewOrCallback): void
    {
        $this->pdfFake()->assertRendered($viewOrCallback);
    }

    protected function assertPdfNotRendered(string|callable $viewOrCallback): void
    {
        $this->pdfFake()->assertNotRendered($viewOrCallback);
    }

    protected function assertPdfDownloaded(?string $filename = null, ?callable $callback = null): void
    {
        $this->pdfFake()->assertDownloaded($filename, $callback);
    }

    protected function assertPdfNotDownloaded(?string $filename = null): void
    {
        $this->pdfFake()->assertNotDownloaded($filename);
    }

    protected function assertPdfInline(?string $filename = null, ?callable $callback = null): void
    {
        $this->pdfFake()->assertInline($filename, $callback);
    }

    protected function assertPdfNotInline(?string $filename = null): void
    {
        $this->pdfFake()->assertNotInline($filename);
    }

    protected function assertPdfSaved(string|callable|null $destinationOrCallback = null): void
    {
        $this->pdfFake()->assertSaved($destinationOrCallback);
    }

    protected function assertPdfStored(string|callable|null $destinationOrCallback = null): void
    {
        $this->pdfFake()->assertStored($destinationOrCallback);
    }

    protected function assertPdfCount(int $expectedCount): void
    {
        $this->pdfFake()->assertCount($expectedCount);
    }

    protected function assertPdfNothingRendered(): void
    {
        $this->pdfFake()->assertNothingRendered();
    }

    protected function assertPdfViewData(string $key, mixed $expectedValue = null, ?string $template = null): void
    {
        $this->pdfFake()->assertViewData($key, $expectedValue, $template);
    }

    protected function assertPdfSee(string $needle, ?string $template = null): void
    {
        $this->pdfFake()->assertSee($needle, $template);
    }
}
