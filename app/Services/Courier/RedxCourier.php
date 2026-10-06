<?php

namespace App\Services\Courier;

use App\Models\CourierShipment;
use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RedxCourier implements CourierInterface
{
    public function code(): string
    {
        return 'redx';
    }

    public function label(): string
    {
        return 'REDX';
    }

    public function isAvailable(): bool
    {
        $config = config('couriers.couriers.redx');

        return (bool) ($config['enabled'] ?? false)
            && in_array($config['environment'] ?? null, ['production', 'sandbox'], true)
            && filled($config['api_token'] ?? null)
            && is_numeric($config['pickup_store_id'] ?? null)
            && (int) ($config['default_weight_grams'] ?? 0) > 0;
    }

    public function supportsCustomerHistory(): bool
    {
        return false;
    }

    public function supportsTrackingApi(): bool
    {
        return true;
    }

    public function supportsInvoiceReconciliation(): bool
    {
        return false;
    }

    public function reconcileShipment(Order $order, CourierShipment $shipment): array
    {
        return [
            'success' => false,
            'message' => 'REDX invoice-based shipment reconciliation is not verified by the current API integration.',
        ];
    }

    public function createShipment(Order $order): array
    {
        if (blank($order->customer_name) || blank($order->phone) || blank($order->address) || blank($order->city) || blank($order->area)) {
            return ['success' => false, 'message' => 'Order delivery details are incomplete for shipment creation.'];
        }

        if (! $this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'REDX requires an enabled merchant token, pickup store, environment, and default parcel weight.',
            ];
        }

        if ($order->payment_method === 'online' && $order->payment_status !== 'paid') {
            return ['success' => false, 'message' => 'Online payment must be confirmed before the parcel can be created.'];
        }

        $deliveryArea = $this->resolveDeliveryArea($order);

        if ($deliveryArea === null) {
            return ['success' => false, 'message' => 'REDX could not match this order’s area. Check the city and area in the customer address.'];
        }

        $payload = [
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->phone,
            'delivery_area' => $deliveryArea['name'],
            'delivery_area_id' => (int) $deliveryArea['id'],
            'customer_address' => $order->address,
            'merchant_invoice_id' => $order->order_number,
            'cash_collection_amount' => (string) ($order->payment_method === 'cod' ? (float) $order->total : 0),
            'parcel_weight' => (int) config('couriers.couriers.redx.default_weight_grams'),
            'instruction' => $order->notes ?? '',
            'value' => max(0, (float) $order->subtotal - (float) $order->discount),
            'pickup_store_id' => (int) config('couriers.couriers.redx.pickup_store_id'),
        ];

        $safePayload = [
            'merchant_invoice_id' => $payload['merchant_invoice_id'],
            'delivery_area_id' => $payload['delivery_area_id'],
            'cash_collection_amount' => $payload['cash_collection_amount'],
            'parcel_weight' => $payload['parcel_weight'],
            'value' => $payload['value'],
            'pickup_store_id' => $payload['pickup_store_id'],
        ];

        try {
            $response = $this->request()->post('parcel', $payload);
        } catch (ConnectionException) {
            return [
                'success' => false,
                'outcome_unknown' => true,
                'request_payload' => $safePayload,
                'message' => 'REDX did not confirm whether the parcel was created. Reconcile it in the merchant portal before retrying.',
            ];
        }

        if (! $response->successful()) {
            $isUncertain = $response->serverError() || in_array($response->status(), [409, 429], true);

            return [
                'success' => false,
                'outcome_unknown' => $isUncertain,
                'request_payload' => $safePayload,
                'response_payload' => ['http_status' => $response->status()],
                'message' => $isUncertain
                    ? 'REDX did not confirm whether the parcel was created. Reconcile it in the merchant portal before retrying.'
                    : 'REDX rejected the parcel request (HTTP '.$response->status().'). Check the merchant account and destination details.',
            ];
        }

        $trackingId = $response->json('tracking_id');

        if (! is_string($trackingId) || trim($trackingId) === '') {
            return [
                'success' => false,
                'outcome_unknown' => true,
                'request_payload' => $safePayload,
                'message' => 'REDX accepted the request without returning a tracking ID. Reconcile it in the merchant portal before retrying.',
            ];
        }

        return [
            'success' => true,
            'external_shipment_id' => $trackingId,
            'consignment_id' => $trackingId,
            'tracking_code' => $trackingId,
            'status' => 'created',
            'request_payload' => $safePayload,
            'response_payload' => ['tracking_id' => $trackingId],
            'shipped_at' => now(),
        ];
    }

    public function trackShipment(CourierShipment $shipment): array
    {
        $trackingId = $shipment->tracking_code ?? $shipment->consignment_id;

        if (! $this->isAvailable() || blank($trackingId)) {
            return [
                'courier' => $this->label(),
                'tracking_code' => $trackingId,
                'current_status' => $shipment->status,
                'available' => false,
                'message' => 'REDX tracking is not configured or this shipment has no tracking ID.',
                'event_history' => [],
            ];
        }

        $cacheKey = 'couriers.redx.tracking.'.hash('sha256', $trackingId);
        $cachedTracking = Cache::get($cacheKey);

        if (is_array($cachedTracking)) {
            return $cachedTracking;
        }

        try {
            $response = $this->request()->get('parcel/info/'.rawurlencode($trackingId));
        } catch (ConnectionException) {
            return [
                'courier' => $this->label(),
                'tracking_code' => $trackingId,
                'current_status' => $shipment->status,
                'available' => false,
                'message' => 'REDX tracking could not be reached. Try again shortly.',
                'event_history' => [],
            ];
        }

        $parcel = $response->json('parcel');

        if (! $response->successful() || ! is_array($parcel)) {
            return [
                'courier' => $this->label(),
                'tracking_code' => $trackingId,
                'current_status' => $shipment->status,
                'available' => false,
                'message' => 'REDX could not return parcel tracking details.',
                'event_history' => [],
            ];
        }

        $tracking = [
            'courier' => $this->label(),
            'tracking_code' => $trackingId,
            'current_status' => $parcel['status'] ?? $shipment->status,
            'last_updated' => $shipment->updated_at?->toISOString(),
            'shipment_date' => $shipment->shipped_at?->toISOString(),
            'available' => true,
            'message' => null,
            'event_history' => [],
        ];

        Cache::put($cacheKey, $tracking, now()->addMinute());

        return $tracking;
    }

    public function getCustomerHistory(string $phone): array
    {
        return [
            'available' => false,
            'message' => 'Customer delivery history is not available from the official REDX API.',
            'total' => null,
            'delivered' => null,
            'failed' => null,
            'returned' => null,
            'success_ratio' => null,
        ];
    }

    public function trackingUrl(CourierShipment $shipment): ?string
    {
        $configured = config('couriers.couriers.redx.tracking_url');

        $host = is_string($configured) ? parse_url($configured, PHP_URL_HOST) : null;

        if (! is_string($host) || ! ($host === 'redx.com.bd' || str_ends_with($host, '.redx.com.bd'))) {
            return null;
        }

        return str_replace('{tracking_code}', (string) ($shipment->tracking_code ?? $shipment->consignment_id), $configured);
    }

    protected function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->withHeader('API-ACCESS-TOKEN', 'Bearer '.config('couriers.couriers.redx.api_token'))
            ->connectTimeout(3)
            ->timeout(12);
    }

    protected function baseUrl(): string
    {
        return match (config('couriers.couriers.redx.environment')) {
            'production' => 'https://openapi.redx.com.bd/v1.0.0-beta',
            'sandbox' => 'https://sandbox.redx.com.bd/v1.0.0-beta',
            default => '',
        };
    }

    protected function resolveDeliveryArea(Order $order): ?array
    {
        $cacheKey = 'couriers.redx.areas.'.hash('sha256', Str::lower(trim($order->city)));
        $areas = Cache::get($cacheKey);

        if (! is_array($areas)) {
            try {
                $response = $this->request()->get('areas', ['district_name' => $order->city]);
            } catch (ConnectionException) {
                return null;
            }

            if (! $response->successful()) {
                return null;
            }

            $areas = $response->json('areas');

            if (! is_array($areas)) {
                return null;
            }

            Cache::put($cacheKey, $areas, now()->addHours(12));
        }

        if (! is_array($areas)) {
            return null;
        }

        foreach ($areas as $area) {
            if (! is_array($area) || ! isset($area['id'], $area['name'])) {
                continue;
            }

            if (Str::lower(trim((string) $area['name'])) === Str::lower(trim((string) $order->area))) {
                return $area;
            }
        }

        return null;
    }
}
