<?php

declare(strict_types=1);

namespace Jengo\Pdf\Documents;

class DeliveryNote extends AbstractDocumentBuilder
{
    protected string $number = 'DN-001';
    protected ?string $date = null;
    protected ?string $orderNumber = null;
    protected array $recipient = [];
    protected array $carrier = [];
    protected array $items = [];
    protected ?string $deliveryInstructions = null;

    public static function make(string $number = 'DN-001'): static
    {
        $instance = new static();
        $instance->number = $number;
        return $instance;
    }

    public function getTemplateName(): string
    {
        return 'delivery_note';
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

    public function orderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;
        return $this;
    }

    public function recipient(
        string $name,
        ?string $address = null,
        ?string $contact = null,
        ?string $phone = null
    ): static {
        $this->recipient = array_filter([
            'name'    => $name,
            'address' => $address,
            'contact' => $contact,
            'phone'   => $phone,
        ], fn($v) => $v !== null);

        return $this;
    }

    public function carrier(
        string $name,
        ?string $trackingNumber = null,
        ?string $vehicleNo = null,
        ?string $driver = null
    ): static {
        $this->carrier = array_filter([
            'name'            => $name,
            'tracking_number' => $trackingNumber,
            'vehicle_no'      => $vehicleNo,
            'driver'          => $driver,
        ], fn($v) => $v !== null);

        return $this;
    }

    public function addItem(
        string $name,
        float|int $qty = 1,
        ?string $sku = null,
        string $packageType = 'Carton',
        ?string $condition = 'Good'
    ): static {
        $this->items[] = [
            'name'         => $name,
            'qty'          => $qty,
            'sku'          => $sku,
            'package_type' => $packageType,
            'condition'    => $condition,
        ];

        return $this;
    }

    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    public function instructions(string $instructions): static
    {
        $this->deliveryInstructions = $instructions;
        return $this;
    }

    public function toDataArray(): array
    {
        return array_merge($this->data, [
            'dnNumber'             => $this->number,
            'deliveryDate'         => $this->date,
            'orderNumber'          => $this->orderNumber,
            'recipient'            => $this->recipient,
            'carrier'              => $this->carrier,
            'items'                => $this->items,
            'deliveryInstructions' => $this->deliveryInstructions,
        ]);
    }
}
