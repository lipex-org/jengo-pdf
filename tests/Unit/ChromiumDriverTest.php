<?php

declare(strict_types=1);

namespace Tests\Unit;

use Jengo\Pdf\Drivers\ChromiumDriver;
use Jengo\Pdf\Exceptions\BinaryNotFoundException;
use Jengo\Pdf\Exceptions\DriverException;
use Jengo\Pdf\PdfDocument;
use Tests\TestCase;

class ChromiumDriverTest extends TestCase
{
    public function testChromiumDriverMetadata(): void
    {
        $driver = new ChromiumDriver();
        $this->assertSame('chromium', $driver->getName());
        $this->assertIsBool($driver->isAvailable());
    }

    public function testChromiumDriverThrowsWhenNodeBinaryNotFound(): void
    {
        $driver = new class(['nodeBinary' => '/nonexistent/path/to/node']) extends ChromiumDriver {
            protected function resolveNodeBinary(): ?string
            {
                return null;
            }
        };

        $this->expectException(BinaryNotFoundException::class);
        $this->expectExceptionMessage('Node.js binary was not found');

        $doc = new PdfDocument();
        $doc->html('<h1>Test</h1>');
        $driver->render($doc);
    }

    public function testChromiumDriverPayloadAndExecutionWithMockNode(): void
    {
        // Create a mock node binary that creates the PDF file specified in config
        $tempDir = WRITEPATH . 'temp/pdf';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $mockNodeScript = $tempDir . '/mock-node.sh';
        file_put_contents($mockNodeScript, "#!/bin/sh\nconfig=\"$2\"\noutput=\$(grep 'outputPath' \"\$config\" | cut -d '\"' -f 4)\necho '%PDF-1.4 Mock Chromium Output' > \"\$output\"\nexit 0\n");
        chmod($mockNodeScript, 0755);

        $driver = new ChromiumDriver(['nodeBinary' => $mockNodeScript]);

        $doc = new PdfDocument();
        $doc->url('https://example.com')
            ->header('<div>Page Header</div>')
            ->footer('<div>Page Footer</div>')
            ->waitForSelector('#chart')
            ->waitForTimeout(100);

        $pdf = $driver->render($doc);
        $this->assertSame("%PDF-1.4 Mock Chromium Output\n", $pdf);

        unlink($mockNodeScript);
    }
}
