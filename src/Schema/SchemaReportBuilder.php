<?php

declare(strict_types=1);

namespace Jengo\Pdf\Schema;

use CodeIgniter\HTTP\ResponseInterface;
use Jengo\Pdf\Contracts\PdfInterface;
use Jengo\Pdf\Contracts\SchemaReportInterface;
use Jengo\Pdf\Enums\Orientation;
use Jengo\Pdf\Enums\PaperFormat;
use Jengo\Pdf\Pdf;
use Jengo\Pdf\PdfDocument;

class SchemaReportBuilder implements SchemaReportInterface
{
    protected string $title = 'Report Summary';
    protected ?string $subtitle = null;
    protected array $columns = [];
    protected array $aggregates = [];
    protected ReportTheme|string|array $theme = 'modern-blue';
    protected ?string $customTemplate = null;
    protected ?string $filename = null;

    protected ?PaperFormat $format = PaperFormat::A4;
    protected ?Orientation $orientation = Orientation::PORTRAIT;

    public function __construct(
        protected mixed $schemaOrQuery
    ) {
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function subtitle(string $subtitle): static
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    public function columns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    public function aggregate(array $aggregates): static
    {
        $this->aggregates = $aggregates;
        return $this;
    }

    public function theme(ReportTheme|string|array $theme): static
    {
        $this->theme = $theme;
        return $this;
    }

    public function template(string $viewPath): static
    {
        $this->customTemplate = $viewPath;
        return $this;
    }

    public function format(PaperFormat|string $format): static
    {
        $this->format = is_string($format) ? PaperFormat::fromName($format) : $format;
        return $this;
    }

    public function orientation(Orientation|string $orientation): static
    {
        $this->orientation = is_string($orientation) ? Orientation::from(strtolower($orientation)) : $orientation;
        return $this;
    }

    public function landscape(): static
    {
        return $this->orientation(Orientation::LANDSCAPE);
    }

    public function portrait(): static
    {
        return $this->orientation(Orientation::PORTRAIT);
    }

    public function filename(string $filename): static
    {
        $this->filename = $filename;
        return $this;
    }

    public function toPdf(): PdfInterface
    {
        $rows = $this->resolveRows();
        $cols = $this->resolveColumns($rows);
        $computedAggregates = $this->computeAggregates($rows, $cols);

        $config = config('Pdf') ?? new \Jengo\Pdf\Config\Pdf();
        $templating = $config->templating ?? [];
        $brand = $templating['brand'] ?? [];
        $defaults = $templating['defaults'] ?? [];
        $theme = ReportTheme::make($this->theme);

        $data = [
            'title'         => $this->title,
            'subtitle'      => $this->subtitle,
            'columns'       => $cols,
            'rows'          => $rows,
            'aggregates'    => $computedAggregates,
            'theme'         => $theme,
            'themeColor'    => $theme->primary,
            'brand'         => $brand,
            'footerText'    => $brand['footer_text'] ?? null,
            'showPoweredBy' => $brand['show_powered_by'] ?? true,
            'dateFormat'    => $defaults['date_format'] ?? 'Y-m-d H:i:s',
        ];

        $html = $this->renderHtml($data);

        $doc = Pdf::html($html)
            ->format($this->format ?? PaperFormat::A4)
            ->orientation($this->orientation ?? Orientation::PORTRAIT);

        if ($this->filename !== null) {
            $doc->filename($this->filename);
        }

        return $doc;
    }

    public function inline(?string $filename = null): ResponseInterface
    {
        $filename = $filename ?? $this->filename;
        return $this->toPdf()->inline($filename);
    }

    public function download(?string $filename = null): ResponseInterface
    {
        $filename = $filename ?? $this->filename;
        return $this->toPdf()->download($filename);
    }

    public function preview(bool $withToolbar = true): ResponseInterface
    {
        return $this->toPdf()->preview($withToolbar);
    }

    public function toHtml(): string
    {
        return $this->toPdf()->toHtml();
    }

    public function save(string $destinationPath): string
    {
        return $this->toPdf()->save($destinationPath);
    }

    public function output(): string
    {
        return $this->toPdf()->output();
    }

    protected function resolveRows(): array
    {
        if (is_array($this->schemaOrQuery)) {
            return array_map(fn($item) => (array) $item, $this->schemaOrQuery);
        }

        if (is_object($this->schemaOrQuery)) {
            if (method_exists($this->schemaOrQuery, 'findAll')) {
                $results = $this->schemaOrQuery->findAll();
                return array_map(fn($item) => (array) $item, is_array($results) ? $results : []);
            }

            if (method_exists($this->schemaOrQuery, 'get') && method_exists($this->schemaOrQuery, 'getResultArray')) {
                return $this->schemaOrQuery->get()->getResultArray();
            }

            if (method_exists($this->schemaOrQuery, 'toArray')) {
                $arr = $this->schemaOrQuery->toArray();
                return array_is_list($arr) ? $arr : [$arr];
            }
        }

        return [];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, Column>
     */
    protected function resolveColumns(array $rows): array
    {
        if (!empty($this->columns)) {
            $parsed = [];
            foreach ($this->columns as $key => $val) {
                if ($val instanceof Column) {
                    $parsed[$val->key] = $val;
                } elseif (is_int($key) && is_string($val)) {
                    $parsed[$val] = Column::make($val, $val);
                } else {
                    $parsed[$key] = Column::make((string) $key, $val);
                }
            }
            return $parsed;
        }

        // Auto-detect columns from first row
        if (!empty($rows)) {
            $first = $rows[0];
            $parsed = [];
            foreach (array_keys($first) as $k) {
                $parsed[$k] = Column::make($k, ucwords(str_replace('_', ' ', $k)));
            }
            return $parsed;
        }

        return [];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, Column> $columns
     * @return array<string, array{label: string, value: float|int|string}>
     */
    protected function computeAggregates(array $rows, array $columns = []): array
    {
        $aggregates = $this->aggregates;

        // Auto-add sum aggregates for summable columns
        foreach ($columns as $colKey => $col) {
            if ($col instanceof Column && $col->isSummable() && !isset($aggregates[$colKey])) {
                $aggregates[$colKey] = 'sum';
            }
        }

        if (empty($aggregates) || empty($rows)) {
            return [];
        }

        $results = [];
        foreach ($aggregates as $field => $type) {
            $type = strtolower((string) $type);
            $values = array_filter(
                array_map(fn($r) => $r[$field] ?? null, $rows),
                fn($v) => is_numeric($v)
            );

            if (empty($values)) {
                continue;
            }

            $computed = match ($type) {
                'sum'   => array_sum($values),
                'avg'   => array_sum($values) / count($values),
                'count' => count($values),
                'min'   => min($values),
                'max'   => max($values),
                default => null,
            };

            if ($computed !== null) {
                $col = $columns[$field] ?? null;
                $formattedValue = $col instanceof Column ? $col->formatValue($computed) : (is_float($computed) ? number_format($computed, 2) : (string) $computed);

                $results[$field] = [
                    'label' => strtoupper($type) . ':',
                    'value' => $formattedValue,
                ];
            }
        }

        return $results;
    }

    protected function renderHtml(array $data): string
    {
        if ($this->customTemplate !== null) {
            return view($this->customTemplate, $data);
        }

        $config = config('Pdf') ?? new \Jengo\Pdf\Config\Pdf();
        $reportView = $config->templating['views']['report'] ?? $config->views['report'] ?? 'Jengo\Pdf\Views\report';

        return view($reportView, $data);
    }
}
