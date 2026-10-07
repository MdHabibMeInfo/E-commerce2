<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SendMetaPurchaseEvent implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 25;

    public function __construct(
        public int $orderId,
        public string $eventSourceUrl,
        public ?string $clientIpAddress,
        public ?string $clientUserAgent,
        public ?string $fbp,
        public ?string $fbc,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 60, 180, 300];
    }

    public function handle(): void
    {
        $pixelId = (string) config('services.meta.pixel_id');
        $accessToken = (string) config('services.meta.capi_access_token');
        $apiVersion = (string) config('services.meta.graph_api_version', 'v25.0');

        if (
            ! (bool) config('services.meta.capi_enabled')
            || ! preg_match('/^\d+$/', $pixelId)
            || $accessToken === ''
            || ! preg_match('/^v\d+\.\d+$/', $apiVersion)
        ) {
            Log::warning('Meta Purchase event skipped because CAPI configuration is incomplete.', [
                'order_id' => $this->orderId,
            ]);

            return;
        }

        $order = Order::query()->with('items')->find($this->orderId);

        if ($order === null) {
            Log::warning('Meta Purchase event skipped because its order no longer exists.', [
                'order_id' => $this->orderId,
            ]);

            return;
        }

        $normalizedPhone = $this->normalizePhone((string) $order->phone);
        $userData = [];

        if ($normalizedPhone !== null) {
            $userData['ph'] = [hash('sha256', $normalizedPhone)];
        }

        if (filter_var($this->clientIpAddress, FILTER_VALIDATE_IP)) {
            $userData['client_ip_address'] = $this->clientIpAddress;
        }

        if (filled($this->clientUserAgent)) {
            $userData['client_user_agent'] = Str::limit((string) $this->clientUserAgent, 500, '');
        }

        foreach (['fbp' => $this->fbp, 'fbc' => $this->fbc] as $key => $value) {
            if (is_string($value) && preg_match('/\A[A-Za-z0-9._-]{1,255}\z/D', $value)) {
                $userData[$key] = $value;
            }
        }

        if ($userData === []) {
            Log::warning('Meta Purchase event skipped because no valid matching identifiers were available.', [
                'order_id' => $order->id,
            ]);

            return;
        }

        $contents = $order->items
            ->map(fn ($item): array => [
                'id' => (string) ($item->variant_sku ?: $item->product_id ?: $item->id),
                'quantity' => (int) $item->quantity,
                'item_price' => (float) $item->price,
            ])
            ->values()
            ->all();

        $event = [
            'event_name' => 'Purchase',
            'event_time' => $order->created_at?->timestamp ?? now()->timestamp,
            'event_id' => 'purchase-order-'.$order->id,
            'event_source_url' => $this->eventSourceUrl,
            'action_source' => 'website',
            'user_data' => $userData,
            'custom_data' => [
                'currency' => (string) config('services.meta.currency', 'BDT'),
                'value' => (float) $order->total,
                'contents' => $contents,
                'content_ids' => array_column($contents, 'id'),
                'content_type' => 'product',
                'delivery_category' => 'home_delivery',
            ],
        ];

        $payload = [
            'data' => json_encode([$event], JSON_THROW_ON_ERROR),
            'access_token' => $accessToken,
        ];
        $testEventCode = config('services.meta.capi_test_event_code');

        if (filled($testEventCode)) {
            $payload['test_event_code'] = (string) $testEventCode;
        }

        $response = Http::asForm()
            ->connectTimeout(3)
            ->timeout(15)
            ->post("https://graph.facebook.com/{$apiVersion}/{$pixelId}/events", $payload);

        if ($response->successful()) {
            if ((int) $response->json('events_received', 0) < 1) {
                Log::warning('Meta Conversions API accepted the request without confirming an event.', [
                    'order_id' => $order->id,
                    'fbtrace_id' => $response->json('fbtrace_id'),
                ]);
            }

            return;
        }

        if ($response->serverError() || $response->status() === 429) {
            throw new RuntimeException('Meta Conversions API temporarily failed with HTTP '.$response->status().'.');
        }

        Log::warning('Meta Conversions API rejected a Purchase event.', [
            'order_id' => $order->id,
            'http_status' => $response->status(),
            'error_code' => $response->json('error.code'),
            'error_subcode' => $response->json('error.error_subcode'),
            'fbtrace_id' => $response->json('error.fbtrace_id'),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Meta Purchase event failed after all queue attempts.', [
            'order_id' => $this->orderId,
            'exception' => $exception !== null ? $exception::class : null,
        ]);
    }

    private function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (preg_match('/^01[3-9]\d{8}$/D', $digits)) {
            return '88'.$digits;
        }

        if (preg_match('/^1[3-9]\d{8}$/D', $digits)) {
            return '880'.$digits;
        }

        if (preg_match('/^8801[3-9]\d{8}$/D', $digits)) {
            return $digits;
        }

        return null;
    }
}
