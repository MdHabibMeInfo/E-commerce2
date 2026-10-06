<?php

namespace App\Services\Courier;

use App\Models\Courier;
use App\Models\CourierShipment;
use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            Log::warning('Courier shipment request failed unexpectedly.', [
                'courier_code' => $courier->code(),
                'order_id' => $order->id,
                'exception' => $exception::class,
            ]);
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

    /** @return array{available: bool, error: bool, courier_code: string, phone: string, total: ?int, delivered: ?int, returned: ?int, cancelled: ?int, failed: ?int, success_ratio: ?float, official_score: ?float, message: ?string} */
    public function customerHistory(string $code, string $phone): array
    {
        $courier = $this->resolve($code);

        if ($courier === null) {
            return $this->unavailableHistory($code, $phone, 'The selected courier is not supported.');
        }

        if (trim($phone) === '') {
            return $this->unavailableHistory($courier->code(), $phone, 'This order does not have a customer phone number.');
        }

        if (! $courier->supportsCustomerHistory()) {
            return $this->unavailableHistory(
                $courier->code(),
                $phone,
                match ($courier->code()) {
                    'redx' => 'Customer delivery history is not available from the REDX API.',
                    'pathao' => 'Customer delivery history is not available from the Pathao API.',
                    'steadfast' => 'Customer delivery history is not available from this courier API because its fraud-check response schema has not been verified.',
                    default => 'Customer delivery history is not available from this courier API.',
                },
            );
        }

        try {
            $history = $courier->getCustomerHistory($phone);
        } catch (Throwable $exception) {
            Log::warning('Courier customer-history lookup failed.', [
                'courier_code' => $courier->code(),
                'exception' => $exception::class,
            ]);

            return $this->unavailableHistory(
                $courier->code(),
                $phone,
                'Unable to retrieve customer delivery history right now.',
                true,
            );
        }

        $available = (bool) ($history['available'] ?? false);
        $total = $this->historyCount($history['total'] ?? null);
        $delivered = $this->historyCount($history['delivered'] ?? null);
        $officialRatio = $history['success_ratio'] ?? null;
        $successRatio = null;

        if ($available && is_numeric($officialRatio) && (float) $officialRatio >= 0 && (float) $officialRatio <= 100) {
            $successRatio = round((float) $officialRatio, 2);
        } elseif ($available && $total !== null && $total > 0 && $delivered !== null && $delivered <= $total) {
            $successRatio = round(($delivered / $total) * 100, 2);
        }

        return [
            'available' => $available,
            'error' => (bool) ($history['error'] ?? false),
            'courier_code' => $courier->code(),
            'phone' => $phone,
            'total' => $total,
            'delivered' => $delivered,
            'returned' => $this->historyCount($history['returned'] ?? null),
            'cancelled' => $this->historyCount($history['cancelled'] ?? null),
            'failed' => $this->historyCount($history['failed'] ?? null),
            'success_ratio' => $successRatio,
            'official_score' => $available && is_numeric($history['official_score'] ?? null)
                ? (float) $history['official_score']
                : null,
            'message' => is_string($history['message'] ?? null) ? $history['message'] : null,
        ];
    }

    public function supportsInvoiceReconciliation(string $code): bool
    {
        $courier = $this->resolve($code);

        return $courier !== null
            && $courier->supportsInvoiceReconciliation()
            && $courier->isAvailable();
    }

    public function reconcileShipment(Order $order, string $code): array
    {
        $courier = $this->resolve($code);
        if ($courier === null) {
            return ['success' => false, 'message' => 'Courier is not available.'];
        }

        $shipment = $order->shipments()->where('courier_code', $courier->code())->first();
        if (! $shipment || $shipment->status !== 'unknown') {
            return ['success' => false, 'message' => 'Only an unknown shipment can be reconciled.'];
        }

        if (! $courier->supportsInvoiceReconciliation()) {
            return ['success' => false, 'message' => $courier->label().' does not support verified invoice-based reconciliation.'];
        }

        $result = $courier->reconcileShipment($order, $shipment);
        if (! ($result['success'] ?? false)) {
            return $result;
        }

        $shipment->forceFill([
            'external_shipment_id' => $result['consignment_id'] ?? $result['tracking_code'] ?? null,
            'consignment_id' => $result['consignment_id'] ?? null,
            'tracking_code' => $result['tracking_code'] ?? null,
            'status' => $result['status'] ?? $shipment->status,
            'request_payload' => $result['request_payload'] ?? ['invoice' => $order->order_number],
            'response_payload' => $result['response_payload'] ?? null,
            'error_message' => null,
            'shipped_at' => $shipment->shipped_at ?? now(),
        ])->save();

        $order->forceFill([
            'courier_name' => $courier->label(),
            'courier_tracking_code' => $shipment->tracking_code ?? $shipment->consignment_id,
            'shipping_status' => $shipment->status,
            'status' => in_array($order->status, ['pending', 'confirmed', 'processing'], true)
                ? 'processing'
                : $order->status,
        ])->save();

        return [
            'success' => true,
            'message' => $courier->label().' shipment reconciled successfully.',
            'shipment_id' => $shipment->id,
            'tracking_code' => $shipment->tracking_code,
            'consignment_id' => $shipment->consignment_id,
            'status' => $shipment->status,
        ];
    }

    /** @return array{available: false, error: bool, courier_code: string, phone: string, total: null, delivered: null, returned: null, cancelled: null, failed: null, success_ratio: null, official_score: null, message: string} */
    private function unavailableHistory(string $courierCode, string $phone, string $message, bool $error = false): array
    {
        return [
            'available' => false,
            'error' => $error,
            'courier_code' => $courierCode,
            'phone' => $phone,
            'total' => null,
            'delivered' => null,
            'returned' => null,
            'cancelled' => null,
            'failed' => null,
            'success_ratio' => null,
            'official_score' => null,
            'message' => $message,
        ];
    }

    private function historyCount(mixed $value): ?int
    {
        if (is_int($value) && $value >= 0) {
            return $value;
        }

        return is_string($value) && ctype_digit($value) ? (int) $value : null;
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
