<?php

namespace App\Services\Courier;

use App\Models\CourierShipment;
use App\Models\Order;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PathaoCourier implements CourierInterface
{
    public function code(): string
    {
        return 'pathao';
    }

    public function label(): string
    {
        return 'Pathao';
    }

    public function isAvailable(): bool
    {
        $config = config('couriers.couriers.pathao');
        $apiUrl = $config['api_url'] ?? null;
        $parts = is_string($apiUrl) ? parse_url($apiUrl) : false;

        return (bool) ($config['enabled'] ?? false)
            && filled($config['client_id'] ?? null)
            && filled($config['client_secret'] ?? null)
            && filled($config['username'] ?? null)
            && filled($config['password'] ?? null)
            && is_numeric($config['store_id'] ?? null)
            && (int) $config['store_id'] > 0
            && is_array($parts)
            && ($parts['scheme'] ?? null) === 'https'
            && in_array(strtolower((string) ($parts['host'] ?? '')), [
                'api-hermes.pathao.com',
                'courier-api-sandbox.pathao.com',
            ], true)
            && rtrim((string) ($parts['path'] ?? ''), '/') === ''
            && ! isset($parts['user'])
            && ! isset($parts['pass'])
            && ! isset($parts['query'])
            && ! isset($parts['fragment']);
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
            'message' => 'Pathao invoice-based shipment reconciliation is not verified by the current API integration.',
        ];
    }

    public function createShipment(Order $order): array
    {
        if (blank($order->customer_name) || blank($order->phone) || blank($order->address) || blank($order->city)) {
            return ['success' => false, 'message' => 'Order delivery details are incomplete for shipment creation.'];
        }

        if (! $this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'Pathao is not active or is missing valid merchant API credentials and store configuration.',
            ];
        }

        if ($order->payment_method !== 'cod' && $order->payment_status !== 'paid') {
            return ['success' => false, 'message' => 'Online payment must be confirmed before the parcel can be created.'];
        }

        $phone = preg_replace('/\D+/', '', (string) $order->phone) ?? '';

        if (str_starts_with($phone, '880') && strlen($phone) === 13) {
            $phone = '0'.substr($phone, 3);
        }

        $addressParts = array_filter([
            trim((string) $order->address),
            trim((string) $order->area),
            trim((string) $order->city),
        ], fn (string $part): bool => $part !== '');
        $recipientAddress = implode(', ', array_values(array_unique($addressParts)));
        $quantity = (int) $order->items()->sum('quantity');
        $weight = (float) config('couriers.couriers.pathao.item_weight', 0.5);
        $deliveryType = (int) config('couriers.couriers.pathao.delivery_type', 48);
        $itemType = (int) config('couriers.couriers.pathao.item_type', 2);

        if (! preg_match('/^01[3-9]\d{8}$/', $phone)) {
            return ['success' => false, 'message' => 'Pathao requires a valid 11-digit Bangladesh recipient phone number.'];
        }

        foreach ([
            'Recipient name' => [$order->customer_name, 3, 100],
            'Recipient address' => [$recipientAddress, 10, 220],
        ] as $label => [$value, $minimumLength, $maximumLength]) {
            if (Str::length((string) $value) < $minimumLength || Str::length((string) $value) > $maximumLength) {
                return ['success' => false, 'message' => $label.' is outside Pathao’s documented length limits.'];
            }
        }

        if ($quantity < 1 || $weight < 0.5 || $weight > 10 || ! in_array($deliveryType, [12, 48], true) || ! in_array($itemType, [1, 2], true)) {
            return ['success' => false, 'message' => 'Pathao parcel quantity, weight, or delivery settings are invalid.'];
        }

        $payload = [
            'store_id' => (int) config('couriers.couriers.pathao.store_id'),
            'merchant_order_id' => (string) $order->order_number,
            'recipient_name' => (string) $order->customer_name,
            'recipient_phone' => $phone,
            'recipient_address' => $recipientAddress,
            'delivery_type' => $deliveryType,
            'item_type' => $itemType,
            'item_quantity' => $quantity,
            'item_weight' => $weight,
            'amount_to_collect' => (int) round($order->payment_method === 'cod' ? max(0, (float) $order->total) : 0),
        ];

        if (filled($order->notes)) {
            $payload['special_instruction'] = (string) $order->notes;
        }

        $safeRequestPayload = [
            'merchant_order_id' => $payload['merchant_order_id'],
            'store_id' => $payload['store_id'],
            'item_quantity' => $payload['item_quantity'],
            'item_weight' => $payload['item_weight'],
            'amount_to_collect' => $payload['amount_to_collect'],
        ];
        $accessToken = $this->accessToken();

        if ($accessToken === null) {
            return [
                'success' => false,
                'request_payload' => $safeRequestPayload,
                'message' => 'Pathao authentication failed. Verify the merchant credentials and account access.',
            ];
        }

        try {
            $response = $this->request($accessToken)->post('aladdin/api/v1/orders', $payload);
        } catch (ConnectionException) {
            return [
                'success' => false,
                'outcome_unknown' => true,
                'request_payload' => $safeRequestPayload,
                'message' => 'Pathao did not confirm whether the order was created. Check the merchant portal before retrying.',
            ];
        }

        if (! $response->successful()) {
            $outcomeUnknown = $response->serverError() || in_array($response->status(), [409, 429], true);

            return [
                'success' => false,
                'outcome_unknown' => $outcomeUnknown,
                'request_payload' => $safeRequestPayload,
                'response_payload' => ['http_status' => $response->status()],
                'message' => $outcomeUnknown
                    ? 'Pathao did not confirm whether the order was created. Check the merchant portal before retrying.'
                    : 'Pathao rejected the order request (HTTP '.$response->status().'). Check the merchant account and delivery details.',
            ];
        }

        $body = $response->json();
        $data = is_array($body) && is_array($body['data'] ?? null) ? $body['data'] : null;
        $consignmentId = $data['consignment_id'] ?? null;

        if (! is_string($consignmentId) && ! is_int($consignmentId)) {
            return [
                'success' => false,
                'outcome_unknown' => true,
                'request_payload' => $safeRequestPayload,
                'response_payload' => ['http_status' => $response->status()],
                'message' => 'Pathao returned no consignment ID. Check the merchant portal before retrying.',
            ];
        }

        $consignmentId = trim((string) $consignmentId);

        if ($consignmentId === '') {
            return [
                'success' => false,
                'outcome_unknown' => true,
                'request_payload' => $safeRequestPayload,
                'response_payload' => ['http_status' => $response->status()],
                'message' => 'Pathao returned no consignment ID. Check the merchant portal before retrying.',
            ];
        }

        $status = $this->normalizedStatus($data['order_status_slug'] ?? $data['order_status'] ?? null);

        return [
            'success' => true,
            'external_shipment_id' => $consignmentId,
            'consignment_id' => $consignmentId,
            'tracking_code' => $consignmentId,
            'status' => $status ?? 'created',
            'request_payload' => $safeRequestPayload,
            'response_payload' => array_filter([
                'consignment_id' => $consignmentId,
                'merchant_order_id' => is_string($data['merchant_order_id'] ?? null) ? $data['merchant_order_id'] : null,
                'order_status' => $status,
                'delivery_fee' => is_numeric($data['delivery_fee'] ?? null) ? (float) $data['delivery_fee'] : null,
            ], fn (mixed $value): bool => $value !== null),
            'shipped_at' => now(),
        ];
    }

    public function trackShipment(CourierShipment $shipment): array
    {
        $consignmentId = $shipment->consignment_id ?? $shipment->external_shipment_id;
        $tracking = [
            'courier' => $this->label(),
            'tracking_code' => $shipment->tracking_code ?? $consignmentId,
            'current_status' => $shipment->status ?? 'created',
            'last_updated' => $shipment->updated_at?->toISOString(),
            'shipment_date' => $shipment->shipped_at?->toISOString(),
            'available' => false,
            'message' => null,
            'event_history' => [],
        ];

        if (! $this->isAvailable() || blank($consignmentId)) {
            return [...$tracking, 'message' => 'Pathao tracking is not configured or this shipment has no consignment ID.'];
        }

        $cacheKey = 'couriers.pathao.tracking.'.hash('sha256', (string) $consignmentId);
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $accessToken = $this->accessToken();

        if ($accessToken === null) {
            return [...$tracking, 'message' => 'Pathao authentication failed. Verify the merchant credentials and account access.'];
        }

        try {
            $response = $this->request($accessToken)->get('aladdin/api/v1/orders/'.rawurlencode((string) $consignmentId).'/info');
        } catch (ConnectionException) {
            return [...$tracking, 'message' => 'Pathao tracking could not be reached. Try again shortly.'];
        }

        $body = $response->json();
        $data = is_array($body) && is_array($body['data'] ?? null) ? $body['data'] : null;

        if (! $response->successful() || $data === null) {
            return [...$tracking, 'message' => 'Pathao could not return parcel tracking details.'];
        }

        $status = $this->normalizedStatus($data['order_status_slug'] ?? $data['order_status'] ?? null);

        if ($status === null) {
            return [...$tracking, 'message' => 'Pathao returned no recognizable parcel status.'];
        }

        $tracking = [
            ...$tracking,
            'current_status' => $status,
            'last_updated' => is_string($data['updated_at'] ?? null) ? $data['updated_at'] : $tracking['last_updated'],
            'available' => true,
        ];

        Cache::put($cacheKey, $tracking, now()->addMinute());

        return $tracking;
    }

    public function getCustomerHistory(string $phone): array
    {
        return [
            'available' => false,
            'message' => 'Customer delivery history is not available from the official Pathao API.',
            'total' => null,
            'delivered' => null,
            'failed' => null,
            'returned' => null,
            'success_ratio' => null,
        ];
    }

    public function trackingUrl(CourierShipment $shipment): ?string
    {
        $configured = config('couriers.couriers.pathao.tracking_url');

        if (empty($configured) || str_contains((string) $configured, 'example.com')) {
            return null;
        }

        return str_replace('{tracking_code}', (string) ($shipment->tracking_code ?? $shipment->consignment_id), $configured);
    }

    private function accessToken(): ?string
    {
        $config = config('couriers.couriers.pathao');
        $baseUrl = rtrim((string) ($config['api_url'] ?? ''), '/');
        $cacheKey = 'couriers.pathao.access-token.'.hash('sha256', json_encode([
            $baseUrl,
            $config['client_id'] ?? null,
            $config['client_secret'] ?? null,
            $config['username'] ?? null,
            $config['password'] ?? null,
        ], JSON_THROW_ON_ERROR));
        $cachedToken = $this->cachedToken($cacheKey);

        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $refreshCacheKey = $cacheKey.'.refresh';
        $refreshToken = $this->cachedToken($refreshCacheKey);
        $tokenResponse = is_string($refreshToken) && $refreshToken !== ''
            ? $this->requestToken([
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ])
            : null;

        if ($tokenResponse === null) {
            $tokenResponse = $this->requestToken([
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'grant_type' => 'password',
                'username' => $config['username'],
                'password' => $config['password'],
            ]);
        }

        $accessToken = $tokenResponse['access_token'] ?? null;
        $expiresIn = $tokenResponse['expires_in'] ?? null;

        if (! is_string($accessToken) || $accessToken === '' || ! is_numeric($expiresIn) || (int) $expiresIn < 1) {
            return null;
        }

        Cache::put($cacheKey, Crypt::encryptString($accessToken), now()->addSeconds(max(1, (int) $expiresIn - 300)));
        $rotatedRefreshToken = $tokenResponse['refresh_token'] ?? $refreshToken;

        if (is_string($rotatedRefreshToken) && $rotatedRefreshToken !== '') {
            Cache::put($refreshCacheKey, Crypt::encryptString($rotatedRefreshToken), now()->addSeconds(max(1, (int) $expiresIn)));
        }

        return $accessToken;
    }

    private function cachedToken(string $key): ?string
    {
        $encryptedToken = Cache::get($key);

        if (! is_string($encryptedToken) || $encryptedToken === '') {
            return null;
        }

        try {
            $token = Crypt::decryptString($encryptedToken);
        } catch (DecryptException) {
            Cache::forget($key);

            return null;
        }

        return $token !== '' ? $token : null;
    }

    private function requestToken(array $payload): ?array
    {
        try {
            $response = Http::baseUrl(rtrim((string) config('couriers.couriers.pathao.api_url'), '/'))
                ->acceptJson()
                ->connectTimeout(3)
                ->timeout(12)
                ->post('aladdin/api/v1/issue-token', $payload);
        } catch (ConnectionException) {
            return null;
        }

        $body = $response->json();

        return $response->successful() && is_array($body) ? $body : null;
    }

    private function request(string $accessToken): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('couriers.couriers.pathao.api_url'), '/'))
            ->acceptJson()
            ->withToken($accessToken)
            ->connectTimeout(3)
            ->timeout(12);
    }

    private function normalizedStatus(mixed $status): ?string
    {
        if (! is_string($status) || trim($status) === '') {
            return null;
        }

        $normalized = Str::lower(trim($status));

        return preg_match('/^[a-z0-9_]+$/', $normalized) ? $normalized : null;
    }
}
