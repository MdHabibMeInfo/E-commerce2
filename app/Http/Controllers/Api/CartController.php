<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $this->resolveCart($request);

        return response()->json($this->payload($cart));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $cart = $this->resolveCart($request);
        $product = Product::findOrFail($validated['product_id']);
        $quantity = (int) ($validated['quantity'] ?? 1);

        $item = $cart->items()->firstOrCreate(
            ['product_id' => $product->id],
            [
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 0,
            ]
        );

        $item->increment('quantity', $quantity);
        $item->update([
            'name' => $product->name,
            'price' => $product->price,
        ]);

        return response()->json($this->payload($cart));
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        if ($validated['quantity'] <= 0) {
            $cartItem->delete();

            return response()->json($this->payload($cartItem->cart));
        }

        $cartItem->update([
            'quantity' => $validated['quantity'],
            'name' => $cartItem->name,
            'price' => $cartItem->price,
        ]);

        return response()->json($this->payload($cartItem->cart));
    }

    public function destroy(CartItem $cartItem)
    {
        $cart = $cartItem->cart;
        $cartItem->delete();

        return response()->json($this->payload($cart));
    }

    protected function resolveCart(Request $request): Cart
    {
        $sessionId = $request->session()->getId();
        $cartId = $request->input('cart_id', $request->session()->get('cart_id'));

        if ($cartId) {
            $cart = Cart::query()->find($cartId);

            if ($cart) {
                $request->session()->put('cart_id', $cart->id);

                return $cart;
            }
        }

        $cart = Cart::query()->firstOrCreate([
            'session_id' => $sessionId,
            'user_id' => $request->user()?->id,
        ]);

        $request->session()->put('cart_id', $cart->id);

        return $cart;
    }

    protected function payload(?Cart $cart): array
    {
        $items = $cart?->items()->get()->map(function (CartItem $item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->name,
                'price' => (float) $item->price,
                'quantity' => (int) $item->quantity,
                'qty' => (int) $item->quantity,
                'subtotal' => (float) $item->price * (int) $item->quantity,
            ];
        })->values()->all() ?? [];

        $quantity = array_sum(array_map(fn ($item) => (int) ($item['quantity'] ?? 0), $items));
        $total = array_sum(array_map(fn ($item) => (float) ($item['subtotal'] ?? 0), $items));

        return [
            'items' => $items,
            'quantity' => $quantity,
            'total' => round($total, 2),
        ];
    }
}
