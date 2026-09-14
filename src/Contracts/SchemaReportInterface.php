<?php

declare(strict_types=1);

namespace Jengo\Pdf\Contracts;

use CodeIgniter\HTTP\ResponseInterface;
use Jengo\Pdf\Schema\ReportTheme;

interface SchemaReportInterface
{
    public function title(string $title): static;

    public function subtitle(string $subtitle): static;

    public function columns(array $columns): static;

    public function aggregate(array $aggregates): static;

    public function theme(ReportTheme|string|array $theme): static;

    public function brand(array|string $brand): static;

    public function logo(string $logo): static;

    public function footer(string $footerText): static;

    public function watermark(
        string|bool|array $textOrConfig = 'JENGO',
        float $opacity = 0.08,
        ?string $color = null,
        ?int $angle = -35,
        ?string $size = null
    ): static;

    public function template(string $viewPath): static;

    public function filename(string $filename): static;

    public function toPdf(): PdfInterface;

    public function inline(?string $filename = null): ResponseInterface;

    public function download(?string $filename = null): ResponseInterface;

    public function preview(bool $withToolbar = true): ResponseInterface;

    public function toHtml(): string;

    public function save(string $destinationPath): string;

    public function output(): string;

    /**
     * Attach filter field definitions for the interactive preview slide-over drawer.
     *
     * @param array<\Jengo\Pdf\Filtering\FilterField> $filters
     */
    public function withFilters(array $filters): static;

    /**
     * Register a callback executed when filters are adjusted in the preview or passed via request.
     *
     * @param callable $callback function(array $filters, \Jengo\Pdf\PdfDocument $doc): void|array
     */
    public function onFilter(callable $callback): static;

    /**
     * Automatically discover and attach filter controls based on schema columns and data types.
     */
    public function withAutoFilters(bool $enabled = true): static;

    /**
     * Get the registered filter field definitions.
     *
     * @return array<\Jengo\Pdf\Filtering\FilterField>
     */
    public function getFilters(): array;
}
