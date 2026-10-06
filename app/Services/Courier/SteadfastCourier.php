<?php

namespace App\Services\Courier;

use App\Models\CourierShipment;
use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SteadfastCourier implements CourierInterface
{
    /** @var list<string> */
    private const DOCUMENTED_STATUSES = [
        'pending',
        'in_review',
        'hold',
        'delivered_approval_pending',
        'partial_delivered_approval_pending',
        'cancelled_approval_pending',
        'unknown_approval_pending',
        'delivered',
        'partial_delivered',
        'cancelled',
        'exceptional',
        'unknown',
    ];

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
        $apiUrl = config('couriers.couriers.steadfast.api_url');
        $parts = is_string($apiUrl) ? parse_url($apiUrl) : false;

        return (bool) config('couriers.couriers.steadfast.enabled')
            && filled(config('couriers.couriers.steadfast.api_key'))
            && filled(config('couriers.couriers.steadfast.secret_key'))
            && is_array($parts)
            && ($parts['scheme'] ?? null) === 'https'
            && strtolower((string) ($parts['host'] ?? '')) === 'portal.packzy.com'
            && rtrim((string) ($parts['path'] ?? ''), '/') === '/api/v1'
            && ! isset($parts['user'])
            && ! isset($parts['pass'])
            && ! isset($parts['query'])
            && ! isset($parts['fragment']);
    }

    public function supportsCustomerHistory(): bool
    {
        return true;
    }

    public function supportsTrackingApi(): bool
    {
        return true;
    }

    public function supportsInvoiceReconciliation(): bool
    {
        return true;
    }

    public function reconcileShipment(Order $order, CourierShipment $shipment): array
    {
        if (! $this->isAvailable()) {
            return ['success' => false, 'message' => 'Steadfast is not active or is missing valid merchant API credentials.'];
        }

        try {
            $response = $this->request()->get('status_by_invoice/'.rawurlencode($order->order_number));
        } catch (ConnectionException) {
            return [
                'success' => false,
                'outcome_unknown' => true,
                'request_payload' => ['invoice' => $order->order_number],
                'message' => 'Steadfast reconciliation could not be reached. The shipment remains unknown.',
            ];
        }

        if (! $response->successful() || ! is_array($body = $response->json())) {
            return [
                'success' => false,
                'outcome_unknown' => $response->serverError() || $response->status() === 429,
                'request_payload' => ['invoice' => $order->order_number],
                'response_payload' => ['http_status' => $response->status()],
                'message' => 'Steadfast could not reconcile this shipment by invoice (HTTP '.$response->status().').',
            ];
        }

        $consignmentId = $this->findScalarField($body, 'consignment_id');
        $trackingCode = $this->findScalarField($body, 'tracking_code');
        $status = $this->findDocumentedStatus($body);

        if ($consignmentId === null && $trackingCode === null) {
            return [
                'success' => false,
                'outcome_unknown' => true,
                'request_payload' => ['invoice' => $order->order_number],
                'response_payload' => ['http_status' => $response->status()],
                'message' => 'Steadfast returned no recognized shipment identifiers. The shipment remains unknown.',
            ];
        }

        $safeResponseMetadata = array_filter([
            'consignment_id' => $consignmentId,
            'tracking_code' => $trackingCode,
            'status' => $status,
        ], fn (mixed $value): bool => $value !== null);

        return [
            'success' => true,
            'consignment_id' => $consignmentId,
            'tracking_code' => $trackingCode,
            'status' => $status ?? $shipment->status,
            'request_payload' => ['invoice' => $order->order_number],
            'response_payload' => $safeResponseMetadata,
        ];
    }

    public function createShipment(Order $order): array
    {
        if (blank($order->customer_name) || blank($order->phone) || blank($order->address)) {
            return ['success' => false, 'message' => 'Order delivery details are incomplete for shipment creation.'];
        }

        if (! $this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'Steadfast is not active or is missing valid merchant API credentials.',
            ];
        }

        if ($order->payment_method !== 'cod' && $order->payment_status !== 'paid') {
            return ['success' => false, 'message' => 'Online payment must be confirmed before the parcel can be created.'];
        }

        $addressParts = array_filter([
            trim((string) $order->address),
            trim((string) $order->area),
            trim((string) $order->city),
        ], fn (string $part): bool => $part !== '');
        $recipientAddress = implode(', ', array_values(array_unique($addressParts)));

        foreach ([
            'Invoice number' => [$order->order_number, 100],
            'Recipient name' => [$order->customer_name, 100],
            'Recipient phone' => [$order->phone, 40],
            'Recipient address' => [$recipientAddress, 490],
        ] as $label => [$value, $maximumLength]) {
            if (Str::length((string) $value) > $maximumLength) {
                return ['success' => false, 'message' => $label.' exceeds Steadfast’s documented length limit.'];
            }
        }

        $codAmount = $order->payment_method === 'cod' ? max(0, (float) $order->total) : 0;
        $payload = [
            'invoice' => $order->order_number,
            'recipient_name' => $order->customer_name,
            'recipient_phone' => $order->phone,
            'recipient_address' => $recipientAddress,
            'cod_amount' => $codAmount,
        ];
        $safeRequestMetadata = [
            'invoice' => $payload['invoice'],
            'cod_amount' => $payload['cod_amount'],
            'payment_method' => $order->payment_method,
        ];

        try {
            $response = $this->request()->post('create_order', $payload);
        } catch (ConnectionException) {
            return [
                'success' => false,
                'outcome_unknown' => true,
                'request_payload' => $safeRequestMetadata,
                'message' => 'Courier response could not be confirmed. Shipment creation must be reconciled before retrying.',
            ];
        }

        if (! $response->successful()) {
            $outcomeUnknown = $response->serverError() || in_array($response->status(), [409, 429], true);

            return [
                'success' => false,
                'outcome_unknown' => $outcomeUnknown,
                'request_payload' => $safeRequestMetadata,
                'response_payload' => ['http_status' => $response->status()],
                'message' => $this->bookingErrorMessage($response->status()),
            ];
        }

        $body = $response->json();
        if (! is_array($body)) {
            return [
                'success' => false,
                'outcome_unknown' => true,
                'request_payload' => $safeRequestMetadata,
                'response_payload' => ['http_status' => $response->status()],
                'message' => 'Courier response could not be confirmed. Shipment creation must be reconciled before retrying.',
            ];
        }

        $consignmentId = $this->findScalarField($body, 'consignment_id');
        $trackingCode = $this->findScalarField($body, 'tracking_code');
        $status = $this->findDocumentedStatus($body);

        if ($consignmentId === null && $trackingCode === null) {
            return [
                'success' => false,
                'outcome_unknown' => true,
                'request_payload' => $safeRequestMetadata,
                'response_payload' => ['http_status' => $response->status()],
                'message' => 'Courier response did not include a recognized shipment ID. Reconcile it before retrying.',
            ];
        }

        $safeResponseMetadata = array_filter([
            'consignment_id' => $consignmentId,
            'tracking_code' => $trackingCode,
            'status' => $status,
        ], fn (mixed $value): bool => $value !== null);

        return [
            'success' => true,
            'external_shipment_id' => $consignmentId ?? $trackingCode,
            'consignment_id' => $consignmentId,
            'tracking_code' => $trackingCode,
            'status' => $status ?? 'created',
            'request_payload' => $safeRequestMetadata,
            'response_payload' => $safeResponseMetadata,
            'shipped_at' => now(),
        ];
    }

    public function trackShipment(CourierShipment $shipment): array
    {
        $identifier = $shipment->consignment_id ?? $shipment->tracking_code;
        $tracking = [
            'courier' => $this->label(),
            'tracking_code' => $shipment->tracking_code ?? $shipment->consignment_id,
            'current_status' => $shipment->status ?? 'created',
            'last_updated' => $shipment->updated_at?->toISOString(),
            'shipment_date' => $shipment->shipped_at?->toISOString(),
            'available' => false,
            'message' => null,
            'event_history' => [],
        ];

        if (! $this->isAvailable() || blank($identifier)) {
            return [...$tracking, 'message' => 'Steadfast tracking is not configured or this shipment has no tracking ID.'];
        }

        $endpoint = filled($shipment->consignment_id)
            ? 'status_by_cid/'.rawurlencode((string) $shipment->consignment_id)
            : 'status_by_trackingcode/'.rawurlencode((string) $shipment->tracking_code);
        $cacheKey = 'couriers.steadfast.tracking.'.hash('sha256', $endpoint);
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = $this->request()->get($endpoint);
        } catch (ConnectionException) {
            return [...$tracking, 'message' => 'Steadfast tracking could not be reached. Try again shortly.'];
        }

        if (! $response->successful() || ! is_array($body = $response->json())) {
            return [...$tracking, 'message' => 'Steadfast could not return parcel tracking details (HTTP '.$response->status().').'];
        }

        $status = $this->findDocumentedStatus($body);
        if ($status === null) {
            return [...$tracking, 'message' => 'Steadfast returned no recognizable parcel status.'];
        }

        $tracking['current_status'] = $status;
        $tracking['available'] = true;
        Cache::put($cacheKey, $tracking, now()->addSeconds(60));

        return $tracking;
    }

    public function getCustomerHistory(string $phone): array
    {
        $normalizedPhone = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($normalizedPhone, '880') && strlen($normalizedPhone) === 13) {
            $normalizedPhone = '0'.substr($normalizedPhone, 3);
        }

        if (! $this->isAvailable()) {
            return $this->unavailableCustomerHistory('Steadfast is not active or is missing valid merchant API credentials.');
        }

        if (! preg_match('/^01[3-9]\d{8}$/', $normalizedPhone)) {
            return $this->unavailableCustomerHistory('A valid 11-digit Bangladesh phone number is required for Steadfast fraud check.');
        }

        $cacheKey = 'couriers.steadfast.customer-history.'.hash('sha256', $normalizedPhone);
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = $this->request()->get('fraud_check/score/'.rawurlencode($normalizedPhone));
        } catch (ConnectionException) {
            return $this->unavailableCustomerHistory('Steadfast customer history could not be reached. Try again shortly.', true);
        }

        $body = $response->json();

        if (! $response->successful() || ! is_array($body)) {
            return $this->unavailableCustomerHistory('Steadfast could not return customer delivery history (HTTP '.$response->status().').', true);
        }

        $total = $this->historyCount($this->findHistoryField($body, ['total_parcel', 'total_parcels', 'total_orders', 'total']));
        $delivered = $this->historyCount($this->findHistoryField($body, ['total_delivered', 'total_delivered_parcel', 'delivered', 'delivered_parcel', 'received', 'successful_deliveries']));
        $returned = $this->historyCount($this->findHistoryField($body, ['total_returned', 'returned', 'returned_parcels']));
        $cancelled = $this->historyCount($this->findHistoryField($body, ['total_cancelled', 'cancelled', 'canceled']));
        $failed = $this->historyCount($this->findHistoryField($body, ['total_failed', 'failed', 'not_delivered']));
        $ratio = $this->historyRatio($this->findHistoryField($body, ['success_ratio', 'success_rate', 'delivery_success_ratio', 'delivery_success_rate']));
        $score = $this->historyRatio($this->findHistoryField($body, ['score', 'fraud_score']));

        if ($total === null || $delivered === null || $delivered > $total) {
            return $this->unavailableCustomerHistory('Steadfast returned an unrecognized customer-history response.');
        }

        $history = [
            'available' => true,
            'error' => false,
            'total' => $total,
            'delivered' => $delivered,
            'returned' => $returned,
            'cancelled' => $cancelled,
            'failed' => $failed,
            'success_ratio' => $ratio,
            'official_score' => $score,
            'message' => null,
        ];

        Cache::put($cacheKey, $history, now()->addMinutes(5));

        return $history;
    }

    public function trackingUrl(CourierShipment $shipment): ?string
    {
        $configured = config('couriers.couriers.steadfast.tracking_url');

        if (empty($configured) || str_contains((string) $configured, 'example.com')) {
            return null;
        }

        return str_replace('{tracking_code}', (string) ($shipment->tracking_code ?? $shipment->consignment_id), $configured);
    }

    protected function request(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('couriers.couriers.steadfast.api_url'), '/'))
            ->acceptJson()
            ->withHeaders([
                'Api-Key' => config('couriers.couriers.steadfast.api_key'),
                'Secret-Key' => config('couriers.couriers.steadfast.secret_key'),
            ])
            ->connectTimeout(3)
            ->timeout(12);
    }

    private function unavailableCustomerHistory(string $message, bool $error = false): array
    {
        return [
            'available' => false,
            'error' => $error,
            'message' => $message,
            'total' => null,
            'delivered' => null,
            'returned' => null,
            'cancelled' => null,
            'failed' => null,
            'success_ratio' => null,
            'official_score' => null,
        ];
    }

    private function findHistoryField(array $payload, array $fields): mixed
    {
        foreach ($payload as $key => $value) {
            if (in_array((string) $key, $fields, true) && (is_int($value) || is_float($value) || is_string($value))) {
                return $value;
            }
        }

        foreach ($payload as $value) {
            if (is_array($value) && ($found = $this->findHistoryField($value, $fields)) !== null) {
                return $found;
            }
        }

        return null;
    }

    private function historyCount(mixed $value): ?int
    {
        if (is_int($value) && $value >= 0) {
            return $value;
        }

        if (is_float($value) && $value >= 0 && floor($value) === $value) {
            return (int) $value;
        }

        return is_string($value) && ctype_digit($value) ? (int) $value : null;
    }

    private function historyRatio(mixed $value): ?float
    {
        return is_numeric($value) && (float) $value >= 0 && (float) $value <= 100
            ? round((float) $value, 2)
            : null;
    }

    /** @param array<string, mixed> $payload */
    private function findScalarField(array $payload, string $field): ?string
    {
        if (array_key_exists($field, $payload) && (is_string($payload[$field]) || is_int($payload[$field]))) {
            $value = trim((string) $payload[$field]);

            if ($value !== '') {
                return $value;
            }
        }

        foreach ($payload as $value) {
            if (is_array($value) && ($found = $this->findScalarField($value, $field)) !== null) {
                return $found;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $payload */
    private function findDocumentedStatus(array $payload): ?string
    {
        $status = $this->findScalarField($payload, 'status');

        return $status !== null && in_array(strtolower($status), self::DOCUMENTED_STATUSES, true)
            ? strtolower($status)
            : null;
    }

    private function bookingErrorMessage(int $status): string
    {
        return match ($status) {
            401 => 'Steadfast rejected the merchant credentials. Verify the API key and secret.',
            403 => 'Steadfast does not permit booking for this merchant account. Check account and KYC status.',
            404 => 'Steadfast booking endpoint was not found. Verify the configured API base URL.',
            409 => 'Steadfast returned a conflict; the shipment outcome must be reconciled before retrying.',
            422 => 'Steadfast rejected the order details. Check the invoice and recipient information.',
            429 => 'Steadfast rate-limited the request. Reconcile the shipment before retrying.',
            default => $status >= 500
                ? 'Courier response could not be confirmed. Shipment creation must be reconciled before retrying.'
                : 'Steadfast rejected the shipment request (HTTP '.$status.').',
        };
    }
}
