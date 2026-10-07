<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CouponService
{
    /** @param Collection<int, Product> $products */
    public function attachCatalogOffers(Collection $products): void
    {
        $campaigns = Coupon::query()
            ->where('is_active', true)
            ->whereIn('target_type', ['all', 'category', 'product', 'variant'])
            ->whereIn('discount_type', ['fixed', 'percentage'])
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->withCount('redemptions')
            ->orderBy('id')
            ->get();

        foreach ($products as $product) {
            $productOffer = $product->type === 'variable'
                ? null
                : $this->catalogOfferFor($campaigns, $product, null, (float) $product->price);

            $product->setAttribute('catalog_offer', $productOffer);

            foreach ($product->variants as $variant) {
                $variant->setAttribute(
                    'catalog_offer',
                    $variant->is_active && ! $variant->is_out_of_stock
                        ? $this->catalogOfferFor($campaigns, $product, (int) $variant->id, (float) $variant->price)
                        : null,
                );
            }
        }
    }

    public function customerKey(?User $user, ?string $phone): string
    {
        if ($user?->account_type === 'customer') {
            $identity = 'user:'.$user->getKey();
        } else {
            $normalizedPhone = preg_replace('/\D+/', '', (string) $phone) ?? '';

            if ($normalizedPhone === '') {
                throw ValidationException::withMessages([
                    'coupon_code' => 'Enter a phone number before applying this coupon.',
                ]);
            }

            $identity = 'phone:'.$normalizedPhone;
        }

        return hash_hmac('sha256', $identity, (string) config('app.key'));
    }

    /** @return array{coupon: Coupon, discount: float, delivery_discount: float, free_delivery: bool, line_discounts: array<int|string, float>} */
    public function calculate(string $code, float $subtotal, string $customerKey, bool $lockForUpdate = false): array
    {
        $coupon = $this->findCoupon($code, $lockForUpdate);

        return $this->calculateCampaign($coupon, [], $subtotal, 0, $customerKey);
    }

    /**
     * @param  list<array{product_id: int, category_id: ?int, product_variant_id: ?int, amount: float}>  $lines
     * @return array{coupon: Coupon, discount: float, delivery_discount: float, free_delivery: bool, line_discounts: array<int|string, float>}
     */
    public function calculateForCart(
        string $code,
        array $lines,
        float $subtotal,
        float $deliveryFee,
        string $customerKey,
        bool $lockForUpdate = false,
    ): array {
        $coupon = $this->findCoupon($code, $lockForUpdate);

        if ($coupon->is_automatic) {
            $this->reject('This offer is applied automatically and does not use a code.');
        }

        return $this->calculateCampaign($coupon, $lines, $subtotal, $deliveryFee, $customerKey);
    }

    /**
     * @param  list<array{product_id: int, category_id: ?int, product_variant_id: ?int, amount: float}>  $lines
     * @return array{coupon: ?Coupon, discount: float, delivery_discount: float, free_delivery: bool, line_discounts: array<int|string, float>}
     */
    public function calculateAutomaticForCart(
        array $lines,
        float $subtotal,
        float $deliveryFee,
        ?string $customerKey,
        bool $lockForUpdate = false,
    ): array {
        $query = Coupon::query()
            ->where('is_automatic', true)
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->orderBy('id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $bestPromotion = null;
        $bestSavings = 0.0;

        foreach ($query->get() as $campaign) {
            try {
                $promotion = $this->calculateCampaign($campaign, $lines, $subtotal, $deliveryFee, $customerKey);
            } catch (ValidationException) {
                continue;
            }

            $savings = $promotion['discount'] + $promotion['delivery_discount'];

            if ($savings > $bestSavings) {
                $bestPromotion = $promotion;
                $bestSavings = $savings;
            }
        }

        return $bestPromotion ?? [
            'coupon' => null,
            'discount' => 0.0,
            'delivery_discount' => 0.0,
            'free_delivery' => false,
            'line_discounts' => [],
        ];
    }

    public function recordRedemption(
        Coupon $coupon,
        Order $order,
        ?User $user,
        string $customerKey,
        float $discount,
        float $deliveryDiscount = 0,
    ): void {
        CouponRedemption::query()->create([
            'coupon_id' => $coupon->getKey(),
            'order_id' => $order->getKey(),
            'user_id' => $user?->account_type === 'customer' ? $user->getKey() : null,
            'customer_key' => $customerKey,
            'discount' => $discount,
            'delivery_discount' => $deliveryDiscount,
            'redeemed_at' => now(),
        ]);
    }

    private function findCoupon(string $code, bool $lockForUpdate): Coupon
    {
        $couponQuery = Coupon::query()->where('code', Str::upper(trim($code)));

        if ($lockForUpdate) {
            $couponQuery->lockForUpdate();
        }

        $coupon = $couponQuery->first();

        if (! $coupon || ! $coupon->is_active) {
            $this->reject('This coupon code is not available.');
        }

        return $coupon;
    }

    /**
     * @param  list<array{product_id: int, category_id: ?int, product_variant_id: ?int, amount: float}>  $lines
     * @return array{coupon: Coupon, discount: float, delivery_discount: float, free_delivery: bool, line_discounts: array<int|string, float>}
     */
    private function calculateCampaign(
        Coupon $coupon,
        array $lines,
        float $subtotal,
        float $deliveryFee,
        ?string $customerKey,
    ): array {
        if (($coupon->starts_at && $coupon->starts_at->isFuture())
            || ($coupon->expires_at && $coupon->expires_at->isPast())) {
            $this->reject('This coupon is not currently valid.');
        }

        if ($subtotal < (float) $coupon->minimum_subtotal) {
            $this->reject('Your order does not meet this coupon’s minimum subtotal.');
        }

        $redemptions = $coupon->redemptions();

        if ($coupon->usage_limit !== null && $redemptions->count() >= $coupon->usage_limit) {
            $this->reject('This coupon has reached its usage limit.');
        }

        if ($customerKey !== null && $coupon->per_customer_limit !== null
            && (clone $redemptions)->where('customer_key', $customerKey)->count() >= $coupon->per_customer_limit) {
            $this->reject('You have already used this coupon the maximum number of times.');
        }

        $eligibleSubtotal = $coupon->target_type === 'all'
            ? ($lines === [] ? $subtotal : collect($lines)->sum('amount'))
            : collect($lines)
                ->filter(fn (array $line): bool => $this->matchesTarget($coupon, $line))
                ->sum('amount');

        if ($coupon->target_type !== 'all' && $eligibleSubtotal <= 0) {
            $this->reject('This offer does not apply to the products in your cart.');
        }

        $freeDelivery = $coupon->discount_type === 'free_shipping';
        $discount = $freeDelivery
            ? 0.0
            : ($coupon->discount_type === 'percentage'
                ? round($eligibleSubtotal * (float) $coupon->discount_value / 100, 2)
                : (float) $coupon->discount_value);

        if (! $freeDelivery && $coupon->maximum_discount !== null) {
            $discount = min($discount, (float) $coupon->maximum_discount);
        }

        $discount = $freeDelivery ? 0.0 : min(round($discount, 2), round($eligibleSubtotal, 2));
        $eligibleLines = collect($lines)
            ->filter(fn (array $line): bool => $this->matchesTarget($coupon, $line))
            ->values();
        $lineDiscounts = [];
        $allocatedDiscount = 0.0;

        foreach ($eligibleLines as $index => $line) {
            $lineKey = $line['cart_item_id'] ?? $index;
            $remainingDiscount = max(0, round($discount - $allocatedDiscount, 2));
            $lineDiscount = $index === $eligibleLines->count() - 1
                ? $remainingDiscount
                : min(
                    $remainingDiscount,
                    round($discount * (float) $line['amount'] / max($eligibleSubtotal, 0.01), 2),
                );
            $lineDiscount = min($lineDiscount, (float) $line['amount']);
            $lineDiscounts[$lineKey] = $lineDiscount;
            $allocatedDiscount += $lineDiscount;
        }

        return [
            'coupon' => $coupon,
            'discount' => $discount,
            'delivery_discount' => $freeDelivery ? round($deliveryFee, 2) : 0.0,
            'free_delivery' => $freeDelivery,
            'line_discounts' => $lineDiscounts,
        ];
    }

    /** @param array{product_id: int, category_id: ?int, product_variant_id: ?int, amount: float} $line */
    private function matchesTarget(Coupon $coupon, array $line): bool
    {
        return match ($coupon->target_type) {
            'category' => $line['category_id'] === $coupon->target_id,
            'product' => $line['product_id'] === $coupon->target_id,
            'variant' => $line['product_variant_id'] === $coupon->target_id,
            default => true,
        };
    }

    /** @return array{discount: float, price: float, badge: string, code: ?string, condition: ?string}|null */
    private function catalogOfferFor(Collection $campaigns, Product $product, ?int $variantId, float $price): ?array
    {
        $bestOffer = null;

        foreach ($campaigns as $campaign) {
            if ($campaign->usage_limit !== null && $campaign->redemptions_count >= $campaign->usage_limit) {
                continue;
            }

            $matches = match ($campaign->target_type) {
                'all' => $campaign->discount_type === 'percentage',
                'category' => (int) $product->category_id === (int) $campaign->target_id,
                'product' => (int) $product->id === (int) $campaign->target_id,
                'variant' => $variantId !== null && $variantId === (int) $campaign->target_id,
                default => false,
            };

            if (! $matches || $price <= 0) {
                continue;
            }

            $discount = $campaign->discount_type === 'percentage'
                ? round($price * (float) $campaign->discount_value / 100, 2)
                : min($price, (float) $campaign->discount_value);

            if ($campaign->maximum_discount !== null) {
                $discount = min($discount, (float) $campaign->maximum_discount);
            }

            $discount = min(round($discount, 2), round($price, 2));

            if ($discount <= (float) ($bestOffer['discount'] ?? 0)) {
                continue;
            }

            $maximumDiscount = $campaign->maximum_discount;
            $percentageValue = rtrim(rtrim(number_format((float) $campaign->discount_value, 2, '.', ''), '0'), '.');
            $badge = $campaign->discount_type === 'percentage'
                ? $percentageValue.'% OFF'
                : '৳'.number_format((float) $campaign->discount_value, 2).' OFF';

            if ($maximumDiscount !== null) {
                $capBadge = 'UP TO ৳'.number_format((float) $maximumDiscount, 2).' OFF';
                $badge = $campaign->discount_type === 'percentage'
                    ? $badge.' · '.$capBadge
                    : ((float) $maximumDiscount < (float) $campaign->discount_value ? $capBadge : $badge);
            }

            $bestOffer = [
                'discount' => $discount,
                'price' => round($price - $discount, 2),
                'badge' => $badge,
                'code' => $campaign->is_automatic ? null : $campaign->code,
                'condition' => (float) $campaign->minimum_subtotal > 0
                    ? 'On orders ৳'.number_format((float) $campaign->minimum_subtotal, 2).'+'
                    : null,
            ];
        }

        return $bestOffer;
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['coupon_code' => $message]);
    }
}
