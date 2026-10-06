<?php

namespace App\Services\Courier;

use App\Models\CourierShipment;
use App\Models\Order;

interface CourierInterface
{
    public function code(): string;

    public function label(): string;

    public function isAvailable(): bool;

    public function supportsCustomerHistory(): bool;

    public function supportsTrackingApi(): bool;

    public function supportsInvoiceReconciliation(): bool;

    public function createShipment(Order $order): array;

    public function reconcileShipment(Order $order, CourierShipment $shipment): array;

    public function trackShipment(CourierShipment $shipment): array;

    public function getCustomerHistory(string $phone): array;

    public function trackingUrl(CourierShipment $shipment): ?string;
}
