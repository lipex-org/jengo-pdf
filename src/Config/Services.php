<?php

declare(strict_types=1);

namespace Jengo\Pdf\Config;

use CodeIgniter\Config\BaseService;
use Jengo\Pdf\PdfDocument;

class Services extends BaseService
{
    /**
     * Return a new or shared PdfDocument instance.
     */
    public static function pdf(bool $getShared = false): PdfDocument
    {
        if ($getShared) {
            return static::getSharedInstance('pdf');
        }

        return new PdfDocument();
    }
}
