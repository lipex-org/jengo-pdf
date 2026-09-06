<?php

declare(strict_types=1);

namespace Jengo\Pdf\Testing;

use Closure;
use CodeIgniter\HTTP\ResponseInterface;
use Jengo\Pdf\PdfDocument;
use PHPUnit\Framework\Assert;

class PdfFake
{
    /**
     * @var array<int, array{
     *     document: PdfDocument,
     *     action: string,
     *     filename: ?string,
     *     destination: ?string,
     *     html: ?string,
     *     view: ?string,
     *     viewData: array,
     *     template: ?string,
     *     format: ?string,
     *     orientation: ?string
     * }>
     */
    protected array $recorded = [];

    /**
     * Record an executed document action.
     */
    public function record(PdfDocument $document, string $action, ?string $filename = null, ?string $destination = null): void
    {
        $this->recorded[] = [
            'document'    => $document,
            'action'      => $action,
            'filename'    => $filename ?? $document->getFilename(),
            'destination' => $destination,
            'html'        => $document->getHtml(),
            'view'        => $document->getView(),
            'viewData'    => $document->getViewData(),
            'template'    => $document->getTemplateName(),
            'format'      => $document->getFormat()?->value,
            'orientation' => $document->getOrientation()?->value,
        ];
    }

    /**
     * Get all recorded PDF interactions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecorded(): array
    {
        return $this->recorded;
    }

    /**
     * Clear all recorded interactions.
     */
    public function clear(): void
    {
        $this->recorded = [];
    }

    /**
     * Assert that a specific template or view was rendered.
     */
    public function assertRendered(string|callable $viewOrCallback): void
    {
        if (is_callable($viewOrCallback)) {
            $matched = array_filter($this->recorded, fn($item) => $viewOrCallback($item['document'], $item));
            Assert::assertNotEmpty(
                $matched,
                'Failed asserting that a PDF matching the given callback was rendered.'
            );
            return;
        }

        $matched = array_filter(
            $this->recorded,
            fn($item) => $item['template'] === $viewOrCallback || $item['view'] === $viewOrCallback
        );

        Assert::assertNotEmpty(
            $matched,
            "Failed asserting that PDF view or template [{$viewOrCallback}] was rendered."
        );
    }

    /**
     * Assert that a specific template or view was NOT rendered.
     */
    public function assertNotRendered(string|callable $viewOrCallback): void
    {
        if (is_callable($viewOrCallback)) {
            $matched = array_filter($this->recorded, fn($item) => $viewOrCallback($item['document'], $item));
            Assert::assertEmpty(
                $matched,
                'Failed asserting that a PDF matching the given callback was NOT rendered.'
            );
            return;
        }

        $matched = array_filter(
            $this->recorded,
            fn($item) => $item['template'] === $viewOrCallback || $item['view'] === $viewOrCallback
        );

        Assert::assertEmpty(
            $matched,
            "Failed asserting that PDF view or template [{$viewOrCallback}] was NOT rendered."
        );
    }

    /**
     * Assert that a PDF was downloaded.
     */
    public function assertDownloaded(?string $filename = null, ?callable $callback = null): void
    {
        $downloads = array_filter($this->recorded, fn($item) => $item['action'] === 'download');

        Assert::assertNotEmpty($downloads, 'Failed asserting that any PDF was downloaded.');

        if ($filename !== null) {
            $matched = array_filter(
                $downloads,
                fn($item) => basename((string) $item['filename']) === basename($filename) || $item['filename'] === $filename
            );
            Assert::assertNotEmpty(
                $matched,
                "Failed asserting that a PDF named [{$filename}] was downloaded."
            );
        }

        if ($callback !== null) {
            $matched = array_filter($downloads, fn($item) => $callback($item['document'], $item));
            Assert::assertNotEmpty(
                $matched,
                'Failed asserting that a downloaded PDF matched the given callback.'
            );
        }
    }

    /**
     * Assert that a PDF was NOT downloaded.
     */
    public function assertNotDownloaded(?string $filename = null): void
    {
        $downloads = array_filter($this->recorded, fn($item) => $item['action'] === 'download');

        if ($filename === null) {
            Assert::assertEmpty($downloads, 'Failed asserting that no PDF was downloaded.');
            return;
        }

        $matched = array_filter(
            $downloads,
            fn($item) => basename((string) $item['filename']) === basename($filename) || $item['filename'] === $filename
        );
        Assert::assertEmpty(
            $matched,
            "Failed asserting that a PDF named [{$filename}] was NOT downloaded."
        );
    }

    /**
     * Assert that a PDF was sent inline to the browser.
     */
    public function assertInline(?string $filename = null, ?callable $callback = null): void
    {
        $inlines = array_filter($this->recorded, fn($item) => $item['action'] === 'inline');

        Assert::assertNotEmpty($inlines, 'Failed asserting that any PDF was served inline.');

        if ($filename !== null) {
            $matched = array_filter(
                $inlines,
                fn($item) => basename((string) $item['filename']) === basename($filename) || $item['filename'] === $filename
            );
            Assert::assertNotEmpty(
                $matched,
                "Failed asserting that an inline PDF named [{$filename}] was served."
            );
        }

        if ($callback !== null) {
            $matched = array_filter($inlines, fn($item) => $callback($item['document'], $item));
            Assert::assertNotEmpty(
                $matched,
                'Failed asserting that an inline PDF matched the given callback.'
            );
        }
    }

    /**
     * Assert that a PDF was saved or stored to disk/storage.
     */
    public function assertSaved(string|callable|null $destinationOrCallback = null): void
    {
        $saved = array_filter($this->recorded, fn($item) => in_array($item['action'], ['save', 'store'], true));

        Assert::assertNotEmpty($saved, 'Failed asserting that any PDF was saved to disk.');

        if (is_string($destinationOrCallback)) {
            $matched = array_filter(
                $saved,
                fn($item) => $item['destination'] === $destinationOrCallback || basename((string) $item['destination']) === basename($destinationOrCallback)
            );
            Assert::assertNotEmpty(
                $matched,
                "Failed asserting that a PDF was saved to destination [{$destinationOrCallback}]."
            );
        } elseif (is_callable($destinationOrCallback)) {
            $matched = array_filter($saved, fn($item) => $destinationOrCallback($item['document'], $item));
            Assert::assertNotEmpty(
                $matched,
                'Failed asserting that a saved PDF matched the given callback.'
            );
        }
    }

    /**
     * Assert that a PDF was stored to storage.
     */
    public function assertStored(string|callable|null $destinationOrCallback = null): void
    {
        $this->assertSaved($destinationOrCallback);
    }

    /**
     * Assert the total count of PDFs generated.
     */
    public function assertCount(int $expectedCount): void
    {
        $actualCount = count($this->recorded);
        Assert::assertSame(
            $expectedCount,
            $actualCount,
            "Failed asserting that {$expectedCount} PDF(s) were generated. Actual count: {$actualCount}."
        );
    }

    /**
     * Assert that nothing was rendered or generated.
     */
    public function assertNothingRendered(): void
    {
        $this->assertCount(0);
    }

    /**
     * Assert that view data has a specific key/value (supports dot notation like 'customer.name').
     */
    public function assertViewData(string $key, mixed $expectedValue = null, ?string $template = null): void
    {
        $records = $template !== null
            ? array_filter($this->recorded, fn($item) => $item['template'] === $template || $item['view'] === $template)
            : $this->recorded;

        Assert::assertNotEmpty($records, 'No rendered PDF records found to check view data.');

        $found = false;
        foreach ($records as $item) {
            $val = $this->getNestedArrayValue($item['viewData'], $key);
            if ($val !== null) {
                if ($expectedValue === null || $val === $expectedValue) {
                    $found = true;
                    break;
                }
            }
        }

        if ($expectedValue !== null) {
            Assert::assertTrue(
                $found,
                "Failed asserting that rendered PDF view data contains key [{$key}] with expected value."
            );
        } else {
            Assert::assertTrue(
                $found,
                "Failed asserting that rendered PDF view data contains key [{$key}]."
            );
        }
    }

    /**
     * Assert that a rendered document or view contains a substring.
     */
    public function assertSee(string $needle, ?string $template = null): void
    {
        $records = $template !== null
            ? array_filter($this->recorded, fn($item) => $item['template'] === $template || $item['view'] === $template)
            : $this->recorded;

        Assert::assertNotEmpty($records, 'No rendered PDF records found to assert content.');

        $found = false;
        foreach ($records as $item) {
            $html = $item['html'] ?? '';
            if (empty($html) && !empty($item['view'])) {
                try {
                    $html = view($item['view'], $item['viewData']);
                } catch (\Throwable) {
                    $html = '';
                }
            }
            if (str_contains($html, $needle)) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue(
            $found,
            "Failed asserting that rendered PDF content contains [{$needle}]."
        );
    }

    /**
     * Helper to resolve dot-notated array values.
     */
    protected function getNestedArrayValue(array $array, string $key): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        $segments = explode('.', $key);
        $current = $array;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }
}
