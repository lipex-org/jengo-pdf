<?php

declare(strict_types=1);

namespace Jengo\Pdf\Documents;

class Invoice extends AbstractDocumentBuilder
{
    protected string $number = 'INV-001';
    protected ?string $date = null;
    protected ?string $dueDate = null;
    protected string $status = 'PAID';
    protected array $customer = [];
    protected array $items = [];
    protected float $taxRate = 0.0;
    protected float $discount = 0.0;
    protected float $shipping = 0.0;
    protected ?string $notes = null;

    public static function make(string $number = 'INV-001'): static
    {
        $instance = new static();
        $instance->number = $number;
        return $instance;
    }

    public function getTemplateName(): string
    {
        return 'invoice';
    }

    public function number(string $number): static
    {
        $this->number = $number;
        return $this;
    }

    public function date(string $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function dueDate(string $dueDate): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function status(string $status): static
    {
        $this->status = strtoupper($status);
        return $this;
    }

    public function paid(): static
    {
        return $this->status('PAID');
    }

    public function unpaid(): static
    {
        return $this->status('UNPAID');
    }

    public function overdue(): static
    {
        return $this->status('OVERDUE');
    }

    public function customer(
        string $name,
        ?string $address = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $taxId = null
    ): static {
        $this->customer = array_filter([
            'name'    => $name,
            'address' => $address,
            'email'   => $email,
            'phone'   => $phone,
            'tax_id'  => $taxId,
        ], fn($v) => $v !== null);

        return $this;
    }

    public function addItem(
        string $name,
        float $price,
        float|int $qty = 1,
        ?string $description = null
    ): static {
        $this->items[] = [
            'name'        => $name,
            'price'       => $price,
            'qty'         => $qty,
            'description' => $description,
            'total'       => $price * $qty,
        ];

        return $this;
    }

    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    public function taxRate(float $rate): static
    {
        $this->taxRate = $rate;
        return $this;
    }

    public function discount(float $amount): static
    {
        $this->discount = $amount;
        return $this;
    }

    public function shipping(float $amount): static
    {
        $this->shipping = $amount;
        return $this;
    }

    public function notes(string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function toDataArray(): array
    {
        return array_merge($this->data, [
            'invoiceNumber' => $this->number,
            'invoiceDate'   => $this->date,
            'dueDate'       => $this->dueDate,
            'status'        => $this->status,
            'customer'      => $this->customer,
            'items'         => $this->items,
            'taxRate'       => $this->taxRate,
            'discount'      => $this->discount,
            'shipping'      => $this->shipping,
            'notes'         => $this->notes,
        ]);
    }
}
