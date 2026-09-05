<?php

declare(strict_types=1);

namespace Jengo\Pdf\Contracts;

use CodeIgniter\HTTP\ResponseInterface;
use Jengo\Pdf\Enums\MediaType;
use Jengo\Pdf\Enums\Orientation;
use Jengo\Pdf\Enums\PaperFormat;
use Jengo\Pdf\Support\Margins;

interface PdfInterface
{
    public function driver(string|DriverInterface $driver): static;

    public function html(string $html): static;

    public function view(string $view, array $data = []): static;

    public function url(string $url): static;

    public function format(PaperFormat|string $format): static;

    public function orientation(Orientation|string $orientation): static;

    public function portrait(): static;

    public function landscape(): static;

    public function margins(float|Margins $top = 10.0, float $right = 10.0, float $bottom = 10.0, float $left = 10.0, string $unit = 'mm'): static;

    public function scale(float $scale): static;

    public function background(bool $showBackground = true): static;

    public function emulateMedia(MediaType|string $media): static;

    public function header(string $html, float $height = 15.0, string $unit = 'mm'): static;

    public function footer(string $html, float $height = 15.0, string $unit = 'mm'): static;

    public function headerView(string $view, array $data = [], float $height = 15.0, string $unit = 'mm'): static;

    public function footerView(string $view, array $data = [], float $height = 15.0, string $unit = 'mm'): static;

    public function pageNumbers(string $format = '{page} / {pages}'): static;

    public function output(): string;

    public function base64(): string;

    public function save(string $destinationPath): string;

    public function filename(string $name): static;

    public function inline(?string $filename = null): ResponseInterface;

    public function download(?string $filename = null): ResponseInterface;
}
