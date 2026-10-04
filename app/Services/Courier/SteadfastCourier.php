<?php

namespace App\Services\Courier;

use App\Models\CourierShipment;
use App\Models\Order;
use App\Models\User;

class SteadfastCourier implements CourierInterface
{
    public function code(): string
    {
        return 'steadfast';
    }

    public function label(): string
    {
        return 'Steadfast';
    }

    public function isAvailable(): bool
    {
        return false;
    }

    public function supportsCustomerHistory(): bool
    {
        return false;
    }

    public function supportsTrackingApi(): bool
    {
        return false;
    }

    public function createShipment(Order $order): array
    {
        if (empty($order->customer_name) || empty($order->phone) || empty($order->address) || empty($order->city)) {
            return ['success' => false, 'message' => 'Order delivery details are incomplete for shipment creation.'];
        }

        if (! $this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'Official Steadfast API credentials and endpoint configuration are required before live shipment creation.',
            ];
        }

        return [
            'success' => false,
            'message' => 'Steadfast live shipment creation is not enabled because the official API contract has not been verified for this installation.',
        ];
    }

    public function trackShipment(CourierShipment $shipment): array
    {
        return [
            'courier' => $this->label(),
            'tracking_code' => $shipment->tracking_code,
            'current_status' => $shipment->status ?? 'created',
            'last_updated' => $shipment->updated_at?->toISOString(),
            'shipment_date' => $shipment->shipped_at?->toISOString(),
            'available' => false,
            'message' => 'Official Steadfast tracking API is not configured for this installation.',
            'event_history' => [],
        ];
    }

    public function getCustomerHistory(User $user): array
    {
        return [
            'available' => false,
            'message' => 'Customer delivery history is not available from the official Steadfast API.',
            'total' => null,
            'delivered' => null,
            'failed' => null,
            'returned' => null,
            'success_ratio' => null,
        ];
    }

    public function trackingUrl(CourierShipment $shipment): ?string
    {
        $configured = config('couriers.couriers.steadfast.tracking_url');

        if (empty($configured) || str_contains((string) $configured, 'example.com')) {
            return null;
        }

        return str_replace('{tracking_code}', (string) ($shipment->tracking_code ?? $shipment->consignment_id), $configured);
    }
}
