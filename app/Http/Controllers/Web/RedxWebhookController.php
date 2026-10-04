<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CourierShipment;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RedxWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $expectedToken = (string) config('couriers.couriers.redx.webhook_token');
        $providedToken = (string) $request->query('token');

        abort_unless($expectedToken !== '' && hash_equals($expectedToken, $providedToken), 404);

        $event = $request->validate([
            'tracking_number' => ['required', 'string', 'max:100'],
            'timestamp' => ['required', 'date'],
            'status' => ['required', 'string', 'max:100'],
            'message_en' => ['nullable', 'string', 'max:1000'],
            'message_bn' => ['nullable', 'string', 'max:1000'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'delivery_type' => ['nullable', 'string', 'max:100'],
        ]);

        $shipment = CourierShipment::query()
            ->with('order')
            ->where('courier_code', 'redx')
            ->where('tracking_code', $event['tracking_number'])
            ->first();

        if (! $shipment) {
            return response()->json(['received' => false], 404);
        }

        $eventTime = CarbonImmutable::parse($event['timestamp']);
        $lastEventTime = data_get($shipment->response_payload, 'redx_webhook.timestamp');

        if (is_string($lastEventTime) && CarbonImmutable::parse($lastEventTime)->greaterThanOrEqualTo($eventTime)) {
            return response()->json(['received' => true, 'duplicate' => true]);
        }

        $safeEvent = Arr::only($event, [
            'timestamp',
            'status',
            'message_en',
            'message_bn',
            'invoice_number',
            'delivery_type',
        ]);
        $safeEvent['timestamp'] = $eventTime->toISOString();
        $providerStatus = strtolower($event['status']);
        $orderStatus = match ($providerStatus) {
            'ready-for-delivery' => 'shipped',
            'delivery-in-progress' => 'out_for_delivery',
            'delivered' => 'delivered',
            'returned' => 'returned',
            'cancelled' => 'cancelled',
            default => null,
        };

        DB::transaction(function () use ($shipment, $safeEvent, $eventTime, $providerStatus, $orderStatus): void {
            $shipment->forceFill([
                'status' => $providerStatus,
                'delivered_at' => $providerStatus === 'delivered' ? $eventTime : $shipment->delivered_at,
                'cancelled_at' => $providerStatus === 'cancelled' ? $eventTime : $shipment->cancelled_at,
                'response_payload' => array_merge($shipment->response_payload ?? [], [
                    'redx_webhook' => $safeEvent,
                ]),
            ])->save();

            if ($orderStatus === null || ! $shipment->order) {
                return;
            }

            $statusPriority = [
                'pending' => 0,
                'confirmed' => 1,
                'processing' => 2,
                'shipped' => 3,
                'out_for_delivery' => 4,
                'delivered' => 5,
                'returned' => 6,
                'cancelled' => 6,
            ];
            $currentStatus = $shipment->order->status;
            $isTerminal = in_array($currentStatus, ['cancelled', 'returned'], true);
            $mayAdvance = ! $isTerminal
                && ($statusPriority[$orderStatus] ?? -1) >= ($statusPriority[$currentStatus] ?? -1);

            $shipment->order->forceFill([
                'shipping_status' => $providerStatus,
                'status' => $mayAdvance ? $orderStatus : $currentStatus,
            ])->save();
        });

        return response()->json(['received' => true]);
    }
}
