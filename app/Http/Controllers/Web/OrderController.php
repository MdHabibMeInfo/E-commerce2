<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Courier\CourierManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()
            ->with('items', 'shipments')
            ->latest()
            ->get();

        return view('account.orders.index', compact('orders'));
    }

    public function history(Request $request): View
    {
        $orders = $request->user()->orders()
            ->whereIn('status', ['delivered', 'cancelled', 'returned'])
            ->with('shipments')
            ->latest()
            ->paginate(10);

        return view('account.orders.history', compact('orders'));
    }

    public function show(Request $request, Order $order, CourierManager $courierManager): View
    {
        $this->authorize('view', $order);

        $order->load('items', 'shipments', 'user');
        $shipment = $order->shipments->last();
        $courier = $shipment ? $courierManager->resolve($shipment->courier_code) : null;
        $tracking = $courier?->supportsTrackingApi()
            ? $courier->trackShipment($shipment)
            : null;

        return view('account.orders.show', compact('order', 'tracking'));
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $cancelled = Order::query()
            ->whereKey($order->getKey())
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('status', 'pending')
            ->whereDoesntHave('shipments')
            ->update([
                'status' => 'cancelled',
            ]);

        if ($cancelled !== 1) {
            return back()->withErrors(['order' => 'This order can no longer be cancelled.']);
        }

        return redirect()->route('account.orders.show', $order)
            ->with('status', 'Your order has been cancelled.');
    }

    public function adminShow(Order $order, CourierManager $courierManager): View
    {
        $this->authorize('view', $order);

        $order->load('items', 'shipments', 'user', 'paymentReviewer');
        $availableCouriers = $courierManager->availableCouriers();

        return view('admin.orders.show', compact('order', 'availableCouriers'));
    }

    public function paymentProof(Order $order): StreamedResponse
    {
        $this->authorize('view', $order);

        $disk = Storage::disk(config('payments.proof_disk', 'local'));
        $path = $order->payment_proof_path;

        abort_unless($path && $disk->exists($path), 404);

        $stream = $disk->readStream($path);
        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $disk->mimeType($path) ?? 'application/octet-stream',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function reviewPayment(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('reviewPayment', $order);

        $validated = $request->validate([
            'payment_status' => ['required', Rule::in(['paid', 'failed'])],
        ]);

        if (! in_array($order->payment_method, ['bkash', 'nagad'], true)) {
            return back()->withErrors(['payment_status' => 'Only mobile wallet payments can be reviewed here.']);
        }

        $updated = Order::query()
            ->whereKey($order->getKey())
            ->whereIn('payment_method', ['bkash', 'nagad'])
            ->where('payment_status', 'pending')
            ->update([
                'payment_status' => $validated['payment_status'],
                'payment_reviewed_by' => $request->user()->getAuthIdentifier(),
                'payment_reviewed_at' => now(),
            ]);

        if ($updated !== 1) {
            return back()->withErrors(['payment_status' => 'This payment has already been reviewed.']);
        }

        $message = $validated['payment_status'] === 'paid'
            ? 'Payment marked as paid.'
            : 'Payment was rejected.';

        return back()->with('success', $message);
    }

    public function createShipment(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('createShipment', $order);

        $courierCode = strtolower((string) $request->input('courier_code', config('couriers.default')));

        $response = app(CourierManager::class)->createShipment($order, $courierCode);

        if (! $response['success']) {
            return back()->withErrors(['courier' => $response['message']]);
        }

        return back()->with('success', $response['message']);
    }
}
