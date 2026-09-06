<?php

declare(strict_types=1);

namespace Jengo\Pdf\Documents;

class Payslip extends AbstractDocumentBuilder
{
    protected ?string $payPeriod = null;
    protected ?string $payDate = null;
    protected array $employee = [];
    protected array $earnings = [];
    protected array $deductions = [];

    public static function make(?string $payPeriod = null): static
    {
        $instance = new static();
        $instance->payPeriod = $payPeriod;
        return $instance;
    }

    public function getTemplateName(): string
    {
        return 'payslip';
    }

    public function period(string $period): static
    {
        $this->payPeriod = $period;
        return $this;
    }

    public function payDate(string $date): static
    {
        $this->payDate = $date;
        return $this;
    }

    public function employee(
        string $name,
        string $id,
        ?string $designation = null,
        ?string $department = null,
        ?string $bankAccount = null,
        ?string $taxId = null
    ): static {
        $this->employee = array_filter([
            'name'         => $name,
            'id'           => $id,
            'designation'  => $designation,
            'department'   => $department,
            'bank_account' => $bankAccount,
            'tax_id'       => $taxId,
        ], fn($v) => $v !== null);

        return $this;
    }

    public function addEarning(string $title, float $amount): static
    {
        $this->earnings[] = [
            'title'  => $title,
            'amount' => $amount,
        ];

        return $this;
    }

    public function earnings(array $earnings): static
    {
        $this->earnings = $earnings;
        return $this;
    }

    public function addDeduction(string $title, float $amount): static
    {
        $this->deductions[] = [
            'title'  => $title,
            'amount' => $amount,
        ];

        return $this;
    }

    public function deductions(array $deductions): static
    {
        $this->deductions = $deductions;
        return $this;
    }

    public function toDataArray(): array
    {
        return array_merge($this->data, [
            'payPeriod'  => $this->payPeriod,
            'payDate'    => $this->payDate,
            'employee'   => $this->employee,
            'earnings'   => $this->earnings,
            'deductions' => $this->deductions,
        ]);
    }
}
