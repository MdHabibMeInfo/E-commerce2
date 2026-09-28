<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function index(Request $request): View
    {
        $cart = $this->resolveCart($request);
        $items = $cart->items()->with(['product.primaryImage', 'product.images'])->get();
        $subtotal = $items->sum(fn ($item) => (float) $item->price * (int) $item->quantity);
        $deliveryFee = $items->isNotEmpty() ? 60.00 : 0.00;
        $discount = 0.00;
        $total = $subtotal + $deliveryFee - $discount;

        return view('storefront.checkout', compact('cart', 'items', 'subtotal', 'deliveryFee', 'discount', 'total'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:25'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:120'],
            'area' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['required', 'in:cod,online'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $cart = $this->resolveCart($request);
        $items = $cart->items()->with('product')->get();

        if ($items->isEmpty()) {
            return back()->withErrors(['cart' => 'Your cart is empty. Add at least one product before placing an order.']);
        }

        foreach ($items as $item) {
            $product = $item->product;

            if (! $product || ! $product->is_active) {
                return back()->withErrors(['cart' => 'One or more products are no longer available.']);
            }

            if ($product->stock < $item->quantity) {
                return back()->withErrors(['cart' => sprintf('%s is out of stock for the requested quantity.', $product->name)]);
            }
        }

        $subtotal = $items->sum(fn ($item) => (float) $item->price * (int) $item->quantity);
        $deliveryFee = 60.00;
        $discount = 0.00;
        $total = $subtotal + $deliveryFee - $discount;

        $order = Order::create([
            'user_id' => $request->user()?->id,
            'session_id' => $request->session()->getId(),
            'customer_name' => $validated['customer_name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'area' => $validated['area'] ?? null,
            'payment_method' => $validated['payment_method'],
            'notes' => $validated['notes'] ?? null,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'discount' => $discount,
            'total' => $total,
        ]);

        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'name' => $item->name,
                'price' => $item->price,
                'quantity' => $item->quantity,
            ]);

            if ($item->product) {
                $item->product->decrement('stock', $item->quantity);
            }
        }

        $cart->items()->delete();
        $request->session()->forget('cart_id');

        return redirect()->route('checkout.success', ['order' => $order->id])->with('success', 'Order placed successfully.');
    }

    public function success(Order $order): View
    {
        return view('storefront.checkout-success', compact('order'));
    }

    protected function resolveCart(Request $request): Cart
    {
        $cartId = $request->input('cart_id', $request->session()->get('cart_id'));

        if ($cartId) {
            $cart = Cart::query()->find($cartId);

            if ($cart) {
                $request->session()->put('cart_id', $cart->id);

                return $cart;
            }
        }

        $cart = Cart::query()->firstOrCreate([
            'session_id' => $request->session()->getId(),
            'user_id' => $request->user()?->id,
        ]);

        $request->session()->put('cart_id', $cart->id);

        return $cart;
    }
}
