<?php

declare(strict_types=1);

namespace Jengo\Pdf\Drivers;

use Jengo\Pdf\Contracts\DriverInterface;
use Jengo\Pdf\PdfDocument;

abstract class AbstractDriver implements DriverInterface
{
    /**
     * Resolve and return HTML string from document (whether raw HTML, View, or URL).
     */
    protected function resolveHtml(PdfDocument $document): string
    {
        if ($document->getHtml() !== null) {
            return $document->getHtml();
        }

        if ($document->getView() !== null) {
            return view($document->getView(), $document->getViewData());
        }

        return '';
    }
}
