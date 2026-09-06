<?php

declare(strict_types=1);

namespace Jengo\Pdf\Documents;

class Quotation extends AbstractDocumentBuilder
{
    protected string $number = 'QUO-001';
    protected ?string $date = null;
    protected ?string $validUntil = null;
    protected array $client = [];
    protected array $items = [];
    protected float $taxRate = 0.0;
    protected float $discount = 0.0;
    protected ?string $terms = null;

    public static function make(string $number = 'QUO-001'): static
    {
        $instance = new static();
        $instance->number = $number;
        return $instance;
    }

    public function getTemplateName(): string
    {
        return 'quotation';
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

    public function validUntil(string $validUntil): static
    {
        $this->validUntil = $validUntil;
        return $this;
    }

    public function client(
        string $name,
        ?string $address = null,
        ?string $email = null,
        ?string $phone = null
    ): static {
        $this->client = array_filter([
            'name'    => $name,
            'address' => $address,
            'email'   => $email,
            'phone'   => $phone,
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

    public function terms(string $terms): static
    {
        $this->terms = $terms;
        return $this;
    }

    public function toDataArray(): array
    {
        return array_merge($this->data, [
            'quoteNumber' => $this->number,
            'quoteDate'   => $this->date,
            'validUntil'  => $this->validUntil,
            'client'      => $this->client,
            'items'       => $this->items,
            'taxRate'     => $this->taxRate,
            'discount'    => $this->discount,
            'terms'       => $this->terms,
        ]);
    }
}
