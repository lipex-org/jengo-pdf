<?php

declare(strict_types=1);

namespace Jengo\Pdf\Documents;

use Jengo\Pdf\Enums\Orientation;

class Certificate extends AbstractDocumentBuilder
{
    protected string $title = 'Certificate of Excellence';
    protected ?string $subtitle = null;
    protected string $recipientName = 'Recipient Name';
    protected ?string $presentationLine = null;
    protected ?string $achievement = null;
    protected ?string $certificateNumber = null;
    protected ?string $issueDate = null;
    protected array $signatories = [];

    public static function make(string $title = 'Certificate of Excellence'): static
    {
        $instance = new static();
        $instance->title = $title;
        $instance->landscape(); // Default certificate orientation to landscape
        return $instance;
    }

    public function getTemplateName(): string
    {
        return 'certificate';
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

    public function recipient(string $name): static
    {
        $this->recipientName = $name;
        return $this;
    }

    public function presentationLine(string $line): static
    {
        $this->presentationLine = $line;
        return $this;
    }

    public function achievement(string $achievement): static
    {
        $this->achievement = $achievement;
        return $this;
    }

    public function number(string $certificateNumber): static
    {
        $this->certificateNumber = $certificateNumber;
        return $this;
    }

    public function issueDate(string $date): static
    {
        $this->issueDate = $date;
        return $this;
    }

    public function addSignatory(string $name, string $title): static
    {
        $this->signatories[] = [
            'name'  => $name,
            'title' => $title,
        ];

        return $this;
    }

    public function signatories(array $signatories): static
    {
        $this->signatories = $signatories;
        return $this;
    }

    public function toDataArray(): array
    {
        return array_merge($this->data, [
            'title'             => $this->title,
            'subtitle'          => $this->subtitle,
            'recipientName'     => $this->recipientName,
            'presentationLine'  => $this->presentationLine,
            'achievement'       => $this->achievement,
            'certificateNumber' => $this->certificateNumber,
            'issueDate'         => $this->issueDate,
            'signatories'       => $this->signatories,
        ]);
    }
}
