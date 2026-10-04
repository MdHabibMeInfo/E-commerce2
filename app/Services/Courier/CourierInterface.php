<?php

namespace App\Services\Courier;

use App\Models\CourierShipment;
use App\Models\Order;
use App\Models\User;

interface CourierInterface
{
    public function code(): string;

    public function label(): string;

    public function isAvailable(): bool;

    public function supportsCustomerHistory(): bool;

    public function supportsTrackingApi(): bool;

    public function createShipment(Order $order): array;

    public function trackShipment(CourierShipment $shipment): array;

    public function getCustomerHistory(User $user): array;

    public function trackingUrl(CourierShipment $shipment): ?string;
}
