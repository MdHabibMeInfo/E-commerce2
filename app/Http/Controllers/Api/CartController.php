<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $cart = $this->resolveCart($request);
        $quantity = (int) ($validated['quantity'] ?? 1);

        DB::transaction(function () use ($cart, $validated, $quantity): void {
            $lockedCart = Cart::query()->lockForUpdate()->findOrFail($cart->id);
            $product = Product::query()->lockForUpdate()->findOrFail($validated['product_id']);
            $variantId = isset($validated['product_variant_id'])
                ? (int) $validated['product_variant_id']
                : null;
            $line = $this->resolvePurchasableLine($product, $variantId);

            $item = $lockedCart->items()
                ->where('line_key', $line['line_key'])
                ->lockForUpdate()
                ->first();
            $newQuantity = (int) ($item?->quantity ?? 0) + $quantity;

            if ($item) {
                $item->update([
                    'product_variant_id' => $line['variant']?->id,
                    'variant_name' => $line['variant_name'],
                    'name' => $product->name,
                    'price' => $line['price'],
                    'quantity' => $newQuantity,
                ]);
            } else {
                $lockedCart->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $line['variant']?->id,
                    'variant_name' => $line['variant_name'],
                    'line_key' => $line['line_key'],
                    'name' => $product->name,
                    'price' => $line['price'],
                    'quantity' => $newQuantity,
                ]);
            }
        });

        return response()->json($this->payload($cart));
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $cart = $this->resolveCart($request);

        abort_unless($cartItem->cart_id === $cart->id, 404);

        if ($validated['quantity'] <= 0) {
            $cartItem->delete();

            return response()->json($this->payload($cartItem->cart));
        }

        DB::transaction(function () use ($cartItem, $validated): void {
            $product = Product::query()->lockForUpdate()->findOrFail($cartItem->product_id);
            $line = $this->resolvePurchasableLine($product, $cartItem->product_variant_id);

            $cartItem->update([
                'variant_name' => $line['variant_name'],
                'name' => $product->name,
                'price' => $line['price'],
                'quantity' => $validated['quantity'],
            ]);
        });

        return response()->json($this->payload($cart));
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        $cart = $this->resolveCart($request);

        abort_unless($cartItem->cart_id === $cart->id, 404);

        $cart = $cartItem->cart;
        $cartItem->delete();

        return response()->json($this->payload($cart));
    }

    protected function resolveCart(Request $request): Cart
    {
        $sessionId = $request->session()->getId();
        $cartId = $request->session()->get('cart_id');

        if ($cartId) {
            $cartQuery = Cart::query()->whereKey($cartId);

            if ($request->user()) {
                $cartQuery->where(function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id)
                        ->orWhereNull('user_id');
                });
            } else {
                $cartQuery->whereNull('user_id');
            }

            $cart = $cartQuery->first();

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

    /**
     * @return array{variant: ProductVariant|null, variant_name: string|null, line_key: string, price: string}
     */
    private function resolvePurchasableLine(Product $product, ?int $variantId): array
    {
        if (! $product->is_active || $product->is_out_of_stock) {
            throw ValidationException::withMessages([
                'product_id' => 'This product is currently unavailable.',
            ]);
        }

        if ($product->type === 'variable') {
            if ($variantId === null) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Select a product variant before adding this product to your cart.',
                ]);
            }

            $variant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->find($variantId);

            if (! $variant) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'The selected product variant is unavailable.',
                ]);
            }

            if ($variant->is_out_of_stock) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'The selected product variant is currently unavailable.',
                ]);
            }

            $variant->load('attributeValues.productAttribute');

            return [
                'variant' => $variant,
                'variant_name' => $variant->displayName(),
                'line_key' => 'variant:'.$variant->id,
                'price' => $variant->price,
            ];
        }

        if ($variantId !== null) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'Simple products cannot be added with a variant.',
            ]);
        }

        if ($product->price === null) {
            throw ValidationException::withMessages([
                'product_id' => 'This product does not have a purchasable price.',
            ]);
        }

        return [
            'variant' => null,
            'variant_name' => null,
            'line_key' => 'product:'.$product->id,
            'price' => $product->price,
        ];
    }

    protected function payload(?Cart $cart): array
    {
        $items = $cart?->items()->with([
            'product.primaryImage',
            'product.images',
            'productVariant.attributeValues.productAttribute',
        ])->get()->map(function (CartItem $item) {
            $product = $item->product;
            $primaryImage = $product?->primaryImage ?? $product?->images?->firstWhere('is_primary', true) ?? $product?->images?->first();

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'variant_name' => $item->variant_name,
                'name' => $item->name,
                'price' => (float) $item->price,
                'quantity' => (int) $item->quantity,
                'qty' => (int) $item->quantity,
                'subtotal' => (float) $item->price * (int) $item->quantity,
                'image_url' => $primaryImage ? '/storage/'.ltrim($primaryImage->image_path, '/') : null,
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
