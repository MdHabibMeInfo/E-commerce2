<?php

namespace App\Services\Courier;

use App\Models\Courier;
use App\Models\CourierShipment;
use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

class CourierManager
{
    /** @var array<string, CourierInterface> */
    protected array $couriers = [];

    public function __construct(
        protected SteadfastCourier $steadfast,
        protected PathaoCourier $pathao,
        protected RedxCourier $redx,
    ) {
        $this->couriers = [
            $this->steadfast->code() => $this->steadfast,
            $this->pathao->code() => $this->pathao,
            $this->redx->code() => $this->redx,
        ];
    }

    public function availableCouriers(): array
    {
        return array_values(array_filter(
            $this->couriers,
            fn (CourierInterface $courier) => $courier->isAvailable(),
        ));
    }

    public function resolve(string $code): ?CourierInterface
    {
        return $this->couriers[strtolower(trim($code))] ?? null;
    }

    public function createShipment(Order $order, string $code): array
    {
        $courier = $this->resolve($code);

        if ($courier === null) {
            return ['success' => false, 'message' => 'Courier is not available.'];
        }

        $shipment = $order->shipments()->where('courier_code', $courier->code())->first();

        if ($shipment && $shipment->status !== 'failed') {
            return ['success' => false, 'message' => $courier->label().' shipment already exists or is being reconciled for this order.'];
        }

        if (! $courier->isAvailable()) {
            return ['success' => false, 'message' => $courier->label().' is not active or configured.'];
        }

        if ($shipment) {
            $claimed = CourierShipment::query()
                ->whereKey($shipment->getKey())
                ->where('status', 'failed')
                ->update([
                    'status' => 'creating',
                    'error_message' => null,
                ]);

            if ($claimed !== 1) {
                return ['success' => false, 'message' => $courier->label().' shipment is already being processed.'];
            }

            $shipment->refresh();
        } else {
            try {
                $shipment = $order->shipments()->create([
                    'courier_code' => $courier->code(),
                    'status' => 'creating',
                ]);
            } catch (QueryException $exception) {
                if ($order->shipments()->where('courier_code', $courier->code())->exists()) {
                    return ['success' => false, 'message' => $courier->label().' shipment is already being processed.'];
                }

                throw $exception;
            }
        }

        try {
            $response = $courier->createShipment($order);
        } catch (Throwable $exception) {
            report($exception);
            $response = [
                'success' => false,
                'outcome_unknown' => true,
                'message' => 'The courier request ended unexpectedly. Check the merchant portal before retrying.',
            ];
        }

        if (! ($response['success'] ?? false)) {
            $shipment->forceFill([
                'status' => ($response['outcome_unknown'] ?? false) ? 'unknown' : 'failed',
                'request_payload' => $response['request_payload'] ?? null,
                'response_payload' => $response['response_payload'] ?? null,
                'error_message' => $response['message'] ?? 'The courier rejected the shipment request.',
            ])->save();

            return $response;
        }

        $record = Courier::query()->firstOrCreate([
            'code' => $courier->code(),
        ], [
            'name' => $courier->label(),
            'is_active' => true,
            'configuration' => [
                'configured' => true,
                'last_synced_at' => now()->toISOString(),
            ],
        ]);

        DB::transaction(function () use ($order, $courier, $record, $response, $shipment): void {
            $shipment->forceFill([
                'courier_id' => $record->id,
                'external_shipment_id' => $response['external_shipment_id'] ?? null,
                'consignment_id' => $response['consignment_id'] ?? null,
                'tracking_code' => $response['tracking_code'] ?? null,
                'status' => $response['status'] ?? 'created',
                'request_payload' => $response['request_payload'] ?? null,
                'response_payload' => $response['response_payload'] ?? null,
                'error_message' => null,
                'shipped_at' => $response['shipped_at'] ?? now(),
            ])->save();

            $order->forceFill([
                'courier_name' => $courier->label(),
                'courier_tracking_code' => $shipment->tracking_code ?? $shipment->consignment_id,
                'shipping_status' => $shipment->status,
                'status' => in_array($order->status, ['pending', 'confirmed', 'processing'], true)
                    ? 'processing'
                    : $order->status,
            ])->save();
        });

        return [
            'success' => true,
            'message' => $courier->label().' shipment created successfully.',
            'shipment_id' => $shipment->id,
            'tracking_code' => $shipment->tracking_code,
            'consignment_id' => $shipment->consignment_id,
            'status' => $shipment->status,
            'courier' => $courier->label(),
        ];
    }

    public function trackingUrl(Order $order): ?string
    {
        $shipment = $order->shipments()->latest()->first();

        if (! $shipment) {
            return null;
        }

        $courier = $this->resolve($shipment->courier_code);

        return $courier?->trackingUrl($shipment);
    }
}
