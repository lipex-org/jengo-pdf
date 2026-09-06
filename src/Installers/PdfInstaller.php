<?php

declare(strict_types=1);

namespace Jengo\Pdf\Installers;

use CodeIgniter\CLI\CLI;
use Jengo\Base\Installers\Contracts\AbstractInstaller;

class PdfInstaller extends AbstractInstaller
{
    public static function name(): string
    {
        return 'pdf';
    }

    public static function description(): string
    {
        return 'Install PDF generation support and publish configuration';
    }

    public static function reasonForSkipping(): string
    {
        return 'PDF configuration already published in app/Config/Pdf.php.';
    }

    public function shouldRun(): bool
    {
        return !file_exists(APPPATH . 'Config/Pdf.php');
    }

    public function install(): void
    {
        $this->addRun();

        $dest = APPPATH . 'Config/Pdf.php';
        if (file_exists($dest)) {
            CLI::write('Config/Pdf.php already exists, skipping.', 'yellow');
            return;
        }

        $source = __DIR__ . '/../Config/Pdf.php';
        $content = file_get_contents($source);
        $content = str_replace("namespace Jengo\\Pdf\\Config;\n\nuse CodeIgniter\\Config\\BaseConfig;", "namespace Config;\n\nuse Jengo\\Pdf\\Config\\Pdf as BasePdf;", $content);
        $content = str_replace("class Pdf extends BaseConfig", "class Pdf extends BasePdf", $content);

        $this->writeFile($dest, $content);
        CLI::write('Published Config/Pdf.php successfully.', 'green');

        // Publish starter templates
        $templatesDir = __DIR__ . '/../Templates';
        $viewsDestDir = APPPATH . 'Views/pdf';
        if (is_dir($templatesDir)) {
            if (!is_dir($viewsDestDir)) {
                mkdir($viewsDestDir, 0777, true);
            }
            $files = glob($templatesDir . '/*.php');
            foreach ($files as $file) {
                $basename = basename($file);
                $target = $viewsDestDir . '/' . $basename;
                if (!file_exists($target)) {
                    $this->writeFile($target, file_get_contents($file));
                }
            }
            CLI::write('Published starter PDF templates to app/Views/pdf/.', 'green');
        }
    }
}
