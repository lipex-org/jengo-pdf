<?php

declare(strict_types=1);

namespace Jengo\Pdf\Documents;

class Receipt extends AbstractDocumentBuilder
{
    protected string $number = 'REC-001';
    protected ?string $date = null;
    protected string $paymentMethod = 'Credit Card';
    protected ?string $transactionRef = null;
    protected array $customer = [];
    protected array $items = [];
    protected float $taxRate = 0.0;
    protected ?float $amountPaid = null;

    public static function make(string $number = 'REC-001'): static
    {
        $instance = new static();
        $instance->number = $number;
        return $instance;
    }

    public function getTemplateName(): string
    {
        return 'receipt';
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

    public function paymentMethod(string $method): static
    {
        $this->paymentMethod = $method;
        return $this;
    }

    public function transactionRef(string $reference): static
    {
        $this->transactionRef = $reference;
        return $this;
    }

    public function customer(
        string $name,
        ?string $email = null,
        ?string $phone = null,
        ?string $address = null
    ): static {
        $this->customer = array_filter([
            'name'    => $name,
            'email'   => $email,
            'phone'   => $phone,
            'address' => $address,
        ], fn($v) => $v !== null);

        return $this;
    }

    public function addItem(
        string $name,
        float $price,
        float|int $qty = 1
    ): static {
        $this->items[] = [
            'name'  => $name,
            'price' => $price,
            'qty'   => $qty,
            'total' => $price * $qty,
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

    public function amountPaid(float $amount): static
    {
        $this->amountPaid = $amount;
        return $this;
    }

    public function toDataArray(): array
    {
        return array_merge($this->data, [
            'receiptNumber'  => $this->number,
            'receiptDate'    => $this->date,
            'paymentMethod'  => $this->paymentMethod,
            'transactionRef' => $this->transactionRef,
            'customer'       => $this->customer,
            'items'          => $this->items,
            'taxRate'        => $this->taxRate,
            'amountPaid'     => $this->amountPaid,
        ]);
    }
}
