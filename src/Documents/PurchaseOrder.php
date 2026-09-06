<?php

declare(strict_types=1);

namespace Jengo\Pdf\Documents;

class PurchaseOrder extends AbstractDocumentBuilder
{
    protected string $number = 'PO-001';
    protected ?string $date = null;
    protected ?string $expectedDate = null;
    protected array $vendor = [];
    protected array $shipTo = [];
    protected array $items = [];
    protected float $taxRate = 0.0;
    protected float $shipping = 0.0;
    protected ?string $paymentTerms = null;
    protected ?string $shippingMethod = null;
    protected ?string $deliveryTerms = null;

    public static function make(string $number = 'PO-001'): static
    {
        $instance = new static();
        $instance->number = $number;
        return $instance;
    }

    public function getTemplateName(): string
    {
        return 'purchase_order';
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

    public function expectedDate(string $date): static
    {
        $this->expectedDate = $date;
        return $this;
    }

    public function vendor(
        string $name,
        ?string $contact = null,
        ?string $address = null,
        ?string $email = null,
        ?string $phone = null
    ): static {
        $this->vendor = array_filter([
            'name'    => $name,
            'contact' => $contact,
            'address' => $address,
            'email'   => $email,
            'phone'   => $phone,
        ], fn($v) => $v !== null);

        return $this;
    }

    public function shipTo(
        string $name,
        ?string $address = null,
        ?string $contact = null,
        ?string $phone = null
    ): static {
        $this->shipTo = array_filter([
            'name'    => $name,
            'address' => $address,
            'contact' => $contact,
            'phone'   => $phone,
        ], fn($v) => $v !== null);

        return $this;
    }

    public function addItem(
        string $name,
        float $price,
        float|int $qty = 1,
        ?string $sku = null
    ): static {
        $this->items[] = [
            'name'  => $name,
            'price' => $price,
            'qty'   => $qty,
            'sku'   => $sku,
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

    public function shipping(float $amount): static
    {
        $this->shipping = $amount;
        return $this;
    }

    public function paymentTerms(string $terms): static
    {
        $this->paymentTerms = $terms;
        return $this;
    }

    public function shippingMethod(string $method): static
    {
        $this->shippingMethod = $method;
        return $this;
    }

    public function deliveryTerms(string $terms): static
    {
        $this->deliveryTerms = $terms;
        return $this;
    }

    public function toDataArray(): array
    {
        return array_merge($this->data, [
            'poNumber'       => $this->number,
            'poDate'         => $this->date,
            'expectedDate'   => $this->expectedDate,
            'vendor'         => $this->vendor,
            'shipTo'         => $this->shipTo,
            'items'          => $this->items,
            'taxRate'        => $this->taxRate,
            'shipping'       => $this->shipping,
            'paymentTerms'   => $this->paymentTerms,
            'shippingMethod' => $this->shippingMethod,
            'deliveryTerms'  => $this->deliveryTerms,
        ]);
    }
}
