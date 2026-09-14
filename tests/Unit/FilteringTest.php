<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\ResponseInterface;
use Jengo\Pdf\Enums\Orientation;
use Jengo\Pdf\Enums\PaperFormat;
use Jengo\Pdf\Filtering\Filter;
use Jengo\Pdf\Filtering\FilterField;
use Jengo\Pdf\Filtering\FilterType;
use Jengo\Pdf\Pdf;
use Jengo\Pdf\PdfDocument;
use Jengo\Pdf\Schema\Column;
use Jengo\Pdf\Schema\SchemaReportBuilder;
use Tests\TestCase;

class FilteringTest extends TestCase
{
    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_SERVER['HTTP_X_JENGO_PDF_FILTER'] = null;
        unset($_SERVER['HTTP_X_JENGO_PDF_FILTER']);

        parent::tearDown();
    }

    public function testFilterTypesAndFieldInstantiation(): void
    {
        $this->assertSame('text', FilterType::Text->value);
        $this->assertSame('search', FilterType::Search->value);
        $this->assertSame('date', FilterType::Date->value);
        $this->assertSame('date_range', FilterType::DateRange->value);
        $this->assertSame('select', FilterType::Select->value);
        $this->assertSame('toggle', FilterType::Toggle->value);
        $this->assertSame('number_range', FilterType::NumberRange->value);

        $field = new FilterField(
            name: 'keyword',
            label: 'Keyword',
            type: FilterType::Search,
            default: 'default-query',
            placeholder: 'Type here...'
        );

        $this->assertSame('keyword', $field->name);
        $this->assertSame('Keyword', $field->label);
        $this->assertSame('search', $field->getType());
        $this->assertSame('default-query', $field->default);
        $this->assertSame('Type here...', $field->placeholder);

        $arr = $field->toArray();
        $this->assertSame('keyword', $arr['name']);
        $this->assertSame('search', $arr['type']);
        $this->assertSame('default-query', $arr['default']);
    }

    public function testFilterFactoryMethodsAndFluentSetters(): void
    {
        $text = Filter::text('author', 'Author', 'Tolstoy', 'Author name')
            ->placeholder('Enter full name')
            ->default('Dostoevsky');
        $this->assertSame('text', $text->getType());
        $this->assertSame('Enter full name', $text->placeholder);
        $this->assertSame('Dostoevsky', $text->default);

        $search = Filter::search('query', 'Find Something', 'initial', 'Search all...')
            ->default('updated');
        $this->assertSame('search', $search->getType());
        $this->assertSame('updated', $search->default);

        $date = Filter::date('start_date', 'Start Date', '2026-01-01');
        $this->assertSame('date', $date->getType());
        $this->assertSame('2026-01-01', $date->default);

        $dateRange = Filter::dateRange('period', 'Date Range', ['from' => '2026-01-01', 'to' => '2026-01-31']);
        $this->assertSame('date_range', $dateRange->getType());
        $this->assertSame(['from' => '2026-01-01', 'to' => '2026-01-31'], $dateRange->default);

        $select = Filter::select('dept', 'Department', ['eng' => 'Engineering', 'sales' => 'Sales'])
            ->multiple(true)
            ->options(['marketing' => 'Marketing']);
        $this->assertSame('select', $select->getType());
        $this->assertTrue($select->multiple);
        $this->assertSame(['marketing' => 'Marketing'], $select->options);

        $toggle = Filter::toggle('include_archived', 'Include Archived', true);
        $this->assertSame('toggle', $toggle->getType());
        $this->assertTrue($toggle->default);

        $numberRange = Filter::numberRange('score', 'Score Range', ['min' => 10, 'max' => 90], 0, 100, 0.5)
            ->min(5.0)
            ->max(95.0)
            ->step(1.0);
        $this->assertSame('number_range', $numberRange->getType());
        $this->assertSame(5.0, $numberRange->min);
        $this->assertSame(95.0, $numberRange->max);
        $this->assertSame(1.0, $numberRange->step);
    }

    public function testFilterFieldHtmlRenderingForAllTypes(): void
    {
        // 1. Text
        $textField = Filter::text('username', 'Username', 'john', 'Enter username');
        $textHtml = $textField->renderHtml('alice');
        $this->assertStringContainsString('data-filter-name="username"', $textHtml);
        $this->assertStringContainsString('data-filter-type="text"', $textHtml);
        $this->assertStringContainsString('value="alice"', $textHtml);
        $this->assertStringContainsString('placeholder="Enter username"', $textHtml);

        // 2. Search
        $searchField = Filter::search('q', 'Search', '', 'Search records...');
        $searchHtml = $searchField->renderHtml('test query');
        $this->assertStringContainsString('data-filter-type="search"', $searchHtml);
        $this->assertStringContainsString('value="test query"', $searchHtml);

        // 3. Date
        $dateField = Filter::date('due_date', 'Due Date');
        $dateHtml = $dateField->renderHtml('2026-09-14');
        $this->assertStringContainsString('type="date"', $dateHtml);
        $this->assertStringContainsString('value="2026-09-14"', $dateHtml);

        // 4. Date Range
        $rangeField = Filter::dateRange('period', 'Period');
        $rangeHtml = $rangeField->renderHtml(['from' => '2026-01-01', 'to' => '2026-06-30']);
        $this->assertStringContainsString('name="period[from]" value="2026-01-01"', $rangeHtml);
        $this->assertStringContainsString('name="period[to]" value="2026-06-30"', $rangeHtml);

        // 5. Select
        $selectField = Filter::select('category', 'Category', ['tech' => 'Technology', 'bio' => 'Biology']);
        $selectHtml = $selectField->renderHtml('bio');
        $this->assertStringContainsString('<select class="jengo-filter-select"', $selectHtml);
        $this->assertStringContainsString('<option value="bio" selected>Biology</option>', $selectHtml);
        $this->assertStringContainsString('<option value="tech">Technology</option>', $selectHtml);

        // 6. Toggle
        $toggleField = Filter::toggle('active', 'Active Only');
        $toggleHtml = $toggleField->renderHtml(true);
        $this->assertStringContainsString('class="jengo-toggle-switch"', $toggleHtml);
        $this->assertStringContainsString('type="checkbox"', $toggleHtml);
        $this->assertStringContainsString('checked', $toggleHtml);

        // 7. Number Range
        $numField = Filter::numberRange('price', 'Price Range', null, 0, 500, 10);
        $numHtml = $numField->renderHtml(['min' => 50, 'max' => 200]);
        $this->assertStringContainsString('name="price[min]" value="50"', $numHtml);
        $this->assertStringContainsString('name="price[max]" value="200"', $numHtml);
        $this->assertStringContainsString('min="0"', $numHtml);
        $this->assertStringContainsString('max="500"', $numHtml);
        $this->assertStringContainsString('step="10"', $numHtml);
    }

    public function testPdfDocumentFilterAttachmentAndCallbackMutation(): void
    {
        $doc = new PdfDocument();
        $filters = [
            Filter::search('search', 'Search'),
            Filter::select('status', 'Status', ['paid' => 'Paid', 'pending' => 'Pending']),
        ];

        $doc->withFilters($filters);
        $this->assertCount(2, $doc->getFilters());
        $this->assertSame('search', $doc->getFilters()[0]->name);

        $doc->portrait();
        $doc->viewData(['title' => 'Initial Title', 'items' => [1, 2, 3]]);

        $doc->onFilter(function (array $submitted, PdfDocument $d): void {
            if (($submitted['status'] ?? null) === 'paid') {
                $d->landscape();
                $d->watermark('PAID IN FULL');
                $d->viewData(['items' => [1]]);
            }
        });

        $this->assertSame(Orientation::PORTRAIT, $doc->getOrientation());

        // Execute filter callback
        $doc->applyFilters(['status' => 'paid']);

        $this->assertSame(Orientation::LANDSCAPE, $doc->getOrientation());
        $this->assertNotNull($doc->getWatermark());
        $this->assertSame('PAID IN FULL', $doc->getWatermark()->text);
        $this->assertSame([1], $doc->getViewData()['items']);
    }

    public function testPdfDocumentFilterCallbackReturningArrayMergesViewData(): void
    {
        $doc = new PdfDocument();
        $doc->viewData(['a' => 1, 'b' => 2]);

        $doc->onFilter(function (array $filters, PdfDocument $d): array {
            return [
                'b' => 99,
                'c' => 100,
            ];
        });

        $doc->applyFilters(['some' => 'filter']);

        $data = $doc->getViewData();
        $this->assertSame(1, $data['a']);
        $this->assertSame(99, $data['b']);
        $this->assertSame(100, $data['c']);
    }

    public function testPreviewRendersSlideOverDrawerWhenFiltersPresent(): void
    {
        $doc = new PdfDocument();
        $doc->html('<div>Preview Content</div>');
        $doc->withFilters([
            Filter::search('search', 'Search Documents'),
            Filter::toggle('active', 'Only Active'),
        ]);

        $response = $doc->preview();
        $html = (string) $response->getBody();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertStringContainsString('id="jengoFilterDrawer"', $html);
        $this->assertStringContainsString('id="jengoDrawerBackdrop"', $html);
        $this->assertStringContainsString('id="jengoFilterToggleBtn"', $html);
        $this->assertStringContainsString('id="jengoFilterForm"', $html);
        $this->assertStringContainsString('data-filter-name="search"', $html);
        $this->assertStringContainsString('data-filter-name="active"', $html);
        $this->assertStringContainsString('function jengoApplyFiltersNow()', $html);
        $this->assertStringContainsString('function jengoToggleFilters()', $html);
        $this->assertStringContainsString('function jengoCloseFilters()', $html);
    }

    public function testPreviewOmitsDrawerWhenNoFiltersPresent(): void
    {
        $doc = new PdfDocument();
        $doc->html('<div>Simple Preview</div>');

        $response = $doc->preview();
        $html = (string) $response->getBody();

        $this->assertStringNotContainsString('id="jengoFilterDrawer"', $html);
        $this->assertStringNotContainsString('id="jengoFilterToggleBtn"', $html);
    }

    public function testPreviewAjaxFilterRequestReturnsJsonResponse(): void
    {
        $_GET['jengo_pdf_filter'] = '1';
        $_GET['search'] = 'Acme Widget';

        $doc = new PdfDocument();
        $doc->html('<h1>Original Output</h1>');
        $doc->withFilters([
            Filter::search('search', 'Search Records'),
        ]);

        $doc->onFilter(function (array $filters, PdfDocument $d): void {
            $query = $filters['search'] ?? '';
            $d->html("<h1>Filtered Output for [{$query}]</h1>");
        });

        $response = $doc->preview();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $json = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($json);
        $this->assertSame('success', $json['status']);
        $this->assertStringContainsString('Filtered Output for [Acme Widget]', $json['html']);
        $this->assertSame('Acme Widget', $json['filters']['search']);
    }

    public function testDownloadAndInlineApplyGetFilters(): void
    {
        $_GET['tier'] = 'vip';

        $doc = new PdfDocument();
        $doc->html('<p>Document</p>');
        $doc->withFilters([
            Filter::select('tier', 'Tier', ['standard' => 'Standard', 'vip' => 'VIP']),
        ]);

        $callbackRan = false;
        $doc->onFilter(function (array $filters, PdfDocument $d) use (&$callbackRan): void {
            $this->assertSame('vip', $filters['tier']);
            $callbackRan = true;
            $d->watermark('VIP CLIENT');
        });

        Pdf::fake();
        $response = $doc->inline('client-doc.pdf');

        $this->assertTrue($callbackRan);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('VIP CLIENT', $doc->getWatermark()->text);
        Pdf::reset();
    }

    public function testSchemaReportWithAutoFiltersAndInMemoryFiltering(): void
    {
        $dataset = [
            ['id' => 1, 'name' => 'Alice Smith', 'role' => 'admin', 'date' => '2026-01-10', 'amount' => 100.0],
            ['id' => 2, 'name' => 'Bob Jones',   'role' => 'editor', 'date' => '2026-03-15', 'amount' => 200.0],
            ['id' => 3, 'name' => 'Charlie Day', 'role' => 'viewer', 'date' => '2026-06-20', 'amount' => 300.0],
            ['id' => 4, 'name' => 'Dana Scully', 'role' => 'admin', 'date' => '2026-08-25', 'amount' => 400.0],
        ];

        $report = Pdf::fromSchema($dataset)
            ->columns([
                'id'     => '# ID',
                'name'   => 'Full Name',
                'role'   => Column::make('role', 'Role')->badge(['admin' => 'danger', 'editor' => 'info', 'viewer' => 'default']),
                'date'   => Column::make('date', 'Date')->date('Y-m-d'),
                'amount' => Column::make('amount', 'Amount')->number(2)->sum(),
            ])
            ->withAutoFilters();

        $pdfDoc = $report->toPdf();
        $this->assertInstanceOf(PdfDocument::class, $pdfDoc);

        $filters = $pdfDoc->getFilters();
        $filterKeys = array_map(fn($f) => $f->name, $filters);

        $this->assertContains('search', $filterKeys);
        $this->assertContains('role', $filterKeys);
        $this->assertContains('date_range', $filterKeys);

        // Test 1: Search filter
        $pdfDoc->applyFilters(['search' => 'Scully']);
        $data = $pdfDoc->getViewData();
        $this->assertCount(1, $data['rows']);
        $this->assertSame('Dana Scully', $data['rows'][0]['name']);
        $this->assertSame(400.0, $data['aggregates']['amount']['raw']);

        // Test 2: Select role filter
        $pdfDoc->applyFilters(['role' => 'admin']);
        $data = $pdfDoc->getViewData();
        $this->assertCount(2, $data['rows']);
        $this->assertSame(500.0, $data['aggregates']['amount']['raw']);

        // Test 3: Date range filter
        $pdfDoc->applyFilters([
            'date_range' => [
                'from' => '2026-02-01',
                'to'   => '2026-07-01',
            ],
        ]);
        $data = $pdfDoc->getViewData();
        $this->assertCount(2, $data['rows']);
        $this->assertSame('Bob Jones', $data['rows'][0]['name']);
        $this->assertSame('Charlie Day', $data['rows'][1]['name']);
        $this->assertSame(500.0, $data['aggregates']['amount']['raw']);
    }

    public function testSchemaReportCustomFilterCallback(): void
    {
        $dataset = [
            ['id' => 1, 'item' => 'Monitor', 'qty' => 5],
            ['id' => 2, 'item' => 'Keyboard', 'qty' => 10],
        ];

        $report = new SchemaReportBuilder($dataset);
        $report->withFilters([
            Filter::numberRange('min_qty', 'Minimum Quantity'),
        ]);

        $customRan = false;
        $report->onFilter(function (array $filters, PdfDocument $doc) use (&$customRan): void {
            $customRan = true;
            $doc->landscape();
        });

        $pdfDoc = $report->toPdf();
        $this->assertSame(Orientation::PORTRAIT, $pdfDoc->getOrientation());

        $pdfDoc->applyFilters(['min_qty' => 8]);
        $this->assertTrue($customRan);
        $this->assertSame(Orientation::LANDSCAPE, $pdfDoc->getOrientation());
    }

    public function testAbstractDocumentBuilderFilterProxying(): void
    {
        $invoice = Pdf::invoice('INV-2026-001')
            ->customer('Wayne Enterprises')
            ->addItem('Consulting', 1, 1500.0)
            ->withFilters([
                Filter::search('search', 'Search Items'),
            ]);

        $this->assertCount(1, $invoice->getFilters());
        $this->assertSame('search', $invoice->getFilters()[0]->name);

        $filterApplied = false;
        $invoice->onFilter(function (array $filters, PdfDocument $doc) use (&$filterApplied): void {
            $filterApplied = true;
            $doc->watermark('SEARCH APPLIED');
        });

        $invoice->applyFilters(['search' => 'Consulting']);
        $this->assertTrue($filterApplied);
    }
}
