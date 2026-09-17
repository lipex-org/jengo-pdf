<?php

declare(strict_types=1);

namespace Jengo\Pdf\Schema;

use CodeIgniter\HTTP\ResponseInterface;
use Jengo\Pdf\Contracts\PdfInterface;
use Jengo\Pdf\Contracts\SchemaReportInterface;
use Jengo\Pdf\Enums\Orientation;
use Jengo\Pdf\Enums\PaperFormat;
use Jengo\Pdf\Filtering\Filter;
use Jengo\Pdf\Filtering\FilterField;
use Jengo\Pdf\Pdf;
use Jengo\Pdf\PdfDocument;

class SchemaReportBuilder implements SchemaReportInterface
{
    protected string $title = 'Report Summary';
    protected ?string $subtitle = null;
    protected array $columns = [];
    protected array $aggregates = [];
    protected ReportTheme|string|array $theme = 'modern-blue';
    protected array|string|null $customBrand = null;
    protected ?string $customLogo = null;
    protected ?string $customFooter = null;
    protected ?string $customTemplate = null;
    protected ?string $filename = null;
    protected string|bool|array|null $watermark = null;

    /** @var array<\Jengo\Pdf\Filtering\FilterField> */
    protected array $filters = [];

    /** @var callable|null */
    protected $filterCallback = null;

    protected bool $autoFilters = false;

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

    public function brand(array|string $brand): static
    {
        $this->customBrand = $brand;
        return $this;
    }

    public function logo(string $logo): static
    {
        $this->customLogo = $logo;
        return $this;
    }

    public function footer(string $footerText): static
    {
        $this->customFooter = $footerText;
        return $this;
    }

    public function watermark(
        string|bool|array $textOrConfig = 'JENGO',
        float $opacity = 0.08,
        ?string $color = null,
        ?int $angle = -35,
        ?string $size = null
    ): static {
        if (is_string($textOrConfig) && ($opacity !== 0.08 || $color !== null || $angle !== -35 || $size !== null)) {
            $this->watermark = [
                'text'    => $textOrConfig,
                'opacity' => $opacity,
                'color'   => $color ?? '#64748b',
                'angle'   => $angle ?? -35,
                'size'    => $size ?? '64pt',
                'enabled' => true,
            ];
        } else {
            $this->watermark = $textOrConfig;
        }

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
     *
     * @param callable $callback function(array $filters, \Jengo\Pdf\PdfDocument $doc): void|array
     */
    public function onFilter(callable $callback): static
    {
        $this->filterCallback = $callback;
        return $this;
    }

    /**
     * Automatically discover and attach filter controls based on schema columns and data types.
     */
    public function withAutoFilters(bool $enabled = true): static
    {
        $this->autoFilters = $enabled;
        return $this;
    }

    /**
     * @return array<\Jengo\Pdf\Filtering\FilterField>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function toPdf(): PdfInterface
    {
        $rows = $this->resolveRows();
        $cols = $this->resolveColumns($rows);
        $computedAggregates = $this->computeAggregates($rows, $cols);

        $config = config('Pdf') ?? new \Jengo\Pdf\Config\Pdf();
        $templating = $config->templating ?? [];
        $brand = $templating['brand'] ?? [];

        if (is_string($this->customBrand)) {
            $brand['name'] = $this->customBrand;
        } elseif (is_array($this->customBrand)) {
            $brand = array_merge($brand, $this->customBrand);
        }

        if ($this->customLogo !== null) {
            $brand['logo'] = $this->customLogo;
        }

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
            'footerText'    => $this->customFooter ?? $brand['footer_text'] ?? null,
            'showPoweredBy' => $brand['show_powered_by'] ?? true,
            'dateFormat'    => $defaults['date_format'] ?? 'Y-m-d H:i:s',
        ];

        $reportView = $this->customTemplate ?? $config->templating['views']['report'] ?? $config->views['report'] ?? 'Jengo\Pdf\Views\report';

        $doc = Pdf::view($reportView, $data)
            ->format($this->format ?? PaperFormat::A4)
            ->orientation($this->orientation ?? Orientation::PORTRAIT);

        if ($this->watermark !== null) {
            $doc->watermark($this->watermark);
        } elseif (isset($defaults['watermark']) && is_array($defaults['watermark']) && !empty($defaults['watermark']['enabled'])) {
            $doc->watermark($defaults['watermark']);
        }

        if ($this->filename !== null) {
            $doc->filename($this->filename);
        }

        $filters = $this->resolveFilters($cols);
        if (!empty($filters)) {
            $doc->withFilters($filters);
        }

        $filterCallback = $this->resolveFilterCallback($rows, $cols);
        if ($filterCallback !== null) {
            $doc->onFilter($filterCallback);
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

            if (method_exists($this->schemaOrQuery, 'get')) {
                $res = $this->schemaOrQuery->get();
                if (is_object($res)) {
                    if (method_exists($res, 'getResultArray')) {
                        return $res->getResultArray();
                    }
                    if (isset($res->data) && is_array($res->data)) {
                        return array_map(fn($item) => (array) $item, $res->data);
                    }
                }
                if (is_array($res)) {
                    return array_map(fn($item) => (array) $item, $res);
                }
            }

            if (method_exists($this->schemaOrQuery, 'getResultArray')) {
                return $this->schemaOrQuery->getResultArray();
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
    public function computeAggregates(array $rows, array $columns = []): array
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
            $values = [];
            foreach ($rows as $r) {
                $v = is_array($r) ? ($r[$field] ?? null) : ($r->{$field} ?? null);
                if ($v === null) {
                    continue;
                }
                if (is_numeric($v)) {
                    $values[] = (float) $v;
                } elseif (is_string($v)) {
                    $cleaned = preg_replace('/[^\d.-]/', '', str_replace(',', '', $v));
                    if ($cleaned !== '' && $cleaned !== null && is_numeric($cleaned)) {
                        $values[] = (float) $cleaned;
                    }
                }
            }

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
                    'raw'   => $computed,
                    'value' => $formattedValue,
                ];
            }
        }

        return $results;
    }

    /**
     * @param array<string, Column> $cols
     * @return array<\Jengo\Pdf\Filtering\FilterField>
     */
    protected function resolveFilters(array $cols): array
    {
        $filters = $this->filters;

        if ($this->autoFilters) {
            $existingKeys = array_map(fn($f) => $f->name, $filters);

            if (!in_array('search', $existingKeys, true)) {
                $filters[] = Filter::search('search', 'Search')
                    ->placeholder('Search report...');
                $existingKeys[] = 'search';
            }

            foreach ($cols as $colKey => $col) {
                if ($col instanceof Column) {
                    if ($col->badgeMap !== null && !in_array($col->key, $existingKeys, true)) {
                        $opts = array_combine(
                            array_keys($col->badgeMap),
                            array_map('ucfirst', array_keys($col->badgeMap))
                        );
                        $filters[] = Filter::select($col->key, $col->label, $opts)
                            ->placeholder('All ' . $col->label);
                        $existingKeys[] = $col->key;
                    } elseif (
                        ($col->format !== null && (str_starts_with($col->format, 'date') || str_starts_with($col->format, 'datetime')))
                        || in_array($col->key, ['created_at', 'updated_at', 'date', 'order_date', 'invoice_date', 'timestamp'], true)
                    ) {
                        $rangeKey = $col->key . '_range';
                        if (!in_array($rangeKey, $existingKeys, true)) {
                            $filters[] = Filter::dateRange($rangeKey, $col->label);
                            $existingKeys[] = $rangeKey;
                        }
                    }
                }
            }
        }

        return $filters;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, Column> $cols
     */
    protected function resolveFilterCallback(array $rows, array $cols): ?callable
    {
        if ($this->filterCallback !== null) {
            return $this->filterCallback;
        }

        if ($this->autoFilters) {
            $builder = $this;
            $allRows = $rows;

            return function (array $filters, PdfDocument $doc) use ($builder, $allRows, $cols): void {
                $filtered = $allRows;

                if (!empty($filters['search'])) {
                    $q = mb_strtolower(trim((string) $filters['search']));
                    $filtered = array_filter($filtered, function ($row) use ($q) {
                        foreach ($row as $val) {
                            if (is_scalar($val) && str_contains(mb_strtolower((string) $val), $q)) {
                                return true;
                            }
                        }
                        return false;
                    });
                }

                foreach ($cols as $colKey => $col) {
                    $rangeKey = $colKey . '_range';
                    if (!empty($filters[$rangeKey]) && is_array($filters[$rangeKey])) {
                        $startStr = $filters[$rangeKey]['from'] ?? $filters[$rangeKey]['start'] ?? null;
                        $endStr = $filters[$rangeKey]['to'] ?? $filters[$rangeKey]['end'] ?? null;
                        $start = !empty($startStr) ? strtotime($startStr . ' 00:00:00') : null;
                        $end = !empty($endStr) ? strtotime($endStr . ' 23:59:59') : null;

                        if ($start !== null || $end !== null) {
                            $filtered = array_filter($filtered, function ($row) use ($colKey, $start, $end) {
                                $val = $row[$colKey] ?? null;
                                if ($val === null) {
                                    return false;
                                }
                                $ts = is_numeric($val) ? (int) $val : strtotime((string) $val);
                                if ($ts === false) {
                                    return true;
                                }
                                if ($start !== null && $ts < $start) {
                                    return false;
                                }
                                if ($end !== null && $ts > $end) {
                                    return false;
                                }
                                return true;
                            });
                        }
                    }

                    if (isset($filters[$colKey]) && $filters[$colKey] !== '' && $filters[$colKey] !== null) {
                        $expected = strtolower((string) $filters[$colKey]);
                        $filtered = array_filter($filtered, function ($row) use ($colKey, $expected) {
                            return isset($row[$colKey]) && strtolower((string) $row[$colKey]) === $expected;
                        });
                    }
                }

                $filtered = array_values($filtered);
                $newAggregates = $builder->computeAggregates($filtered, $cols);

                $doc->viewData([
                    'rows'       => $filtered,
                    'aggregates' => $newAggregates,
                ]);
            };
        }

        return null;
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
