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
}
