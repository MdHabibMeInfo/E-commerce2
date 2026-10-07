<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\SecurityEventService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminCouponController extends Controller
{
    public function index(Request $request): View
    {
        $query = Coupon::query()->withCount('redemptions');
        $search = $request->string('search')->trim()->toString();

        if ($search !== '') {
            $like = '%'.addcslashes($search, '\\%_').'%';
            $query->where('code', 'like', $like);
        }

        if ($request->input('promotion_type') === 'coupon') {
            $query->where('is_automatic', false);
        } elseif ($request->input('promotion_type') === 'automatic') {
            $query->where('is_automatic', true);
        }

        if (in_array($request->input('discount_type'), ['fixed', 'percentage', 'free_shipping'], true)) {
            $query->where('discount_type', $request->input('discount_type'));
        }

        if (in_array($request->input('target_type'), ['all', 'category', 'product', 'variant'], true)) {
            $query->where('target_type', $request->input('target_type'));
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $coupons = $query->latest()->paginate(25)->withQueryString();
        $couponTargetLabels = $this->resolveTargetLabels($coupons);
        $oldTargetLabel = old('target') ? $this->resolveSingleTargetLabel(old('target')) : null;

        return view('admin.coupons.index', compact('coupons', 'search', 'couponTargetLabels', 'oldTargetLabel'));
    }

    public function targetSuggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['required', 'string', 'min:2', 'max:80'],
        ]);
        $term = addcslashes($validated['search'], '\\%_');
        $like = '%'.$term.'%';

        $categories = Category::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('slug', 'like', $like))
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name'])
            ->map(fn (Category $category): array => [
                'value' => 'category:'.$category->id,
                'label' => $category->name,
                'type' => 'Category',
            ]);

        $products = Product::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('name', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhere('slug', 'like', $like))
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name'])
            ->map(fn (Product $product): array => [
                'value' => 'product:'.$product->id,
                'label' => $product->name,
                'type' => 'Product',
            ]);

        $variants = ProductVariant::query()
            ->where('is_active', true)
            ->where(function ($query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhereHas('product', fn ($product) => $product->where('name', 'like', $like));
            })
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->with(['product:id,name', 'attributeValues.productAttribute'])
            ->orderBy('product_id')
            ->orderBy('id')
            ->limit(6)
            ->get()
            ->map(fn (ProductVariant $variant): array => [
                'value' => 'variant:'.$variant->id,
                'label' => $variant->product->name.' - '.$variant->displayName(),
                'type' => 'Variant',
            ]);

        return response()->json(['items' => $categories->concat($products)->concat($variants)->values()]);
    }

    public function store(Request $request, SecurityEventService $events): RedirectResponse
    {
        $validated = $this->validatedCoupon($request);
        $validated['created_by'] = $request->user()->getAuthIdentifier();
        $coupon = Coupon::query()->create($validated);
        $events->record($request, $request->user(), 'coupon_created', [
            'coupon_id' => $coupon->getKey(),
            'code' => $coupon->code,
        ]);

        return redirect()->route('admin.coupons.index')->with('success', 'Promotion created.');
    }

    public function update(Request $request, Coupon $coupon, SecurityEventService $events): RedirectResponse
    {
        abort_if($coupon->redemptions()->exists(), 409, 'A coupon cannot be edited after it has been redeemed.');

        $validated = $this->validatedCoupon($request, $coupon);
        $coupon->update($validated);
        $events->record($request, $request->user(), 'coupon_updated', [
            'coupon_id' => $coupon->getKey(),
            'code' => $coupon->code,
        ]);

        return redirect()->route('admin.coupons.index')->with('success', 'Promotion updated.');
    }

    public function updateStatus(Request $request, Coupon $coupon, SecurityEventService $events): RedirectResponse
    {
        $validated = $request->validate(['is_active' => ['required', 'boolean']]);
        $previousStatus = $coupon->is_active;
        $coupon->is_active = $validated['is_active'];
        $coupon->save();
        $events->record($request, $request->user(), $coupon->is_active ? 'coupon_activated' : 'coupon_deactivated', [
            'coupon_id' => $coupon->getKey(),
            'code' => $coupon->code,
            'previous_status' => $previousStatus,
        ]);

        return redirect()->route('admin.coupons.index')->with('success', 'Promotion status updated.');
    }

    /** @return array<string, mixed> */
    private function validatedCoupon(Request $request, ?Coupon $coupon = null): array
    {
        $uniqueCode = Rule::unique('coupons', 'code');

        if ($coupon) {
            $uniqueCode->ignore($coupon->getKey());
        }

        $request->merge([
            'is_automatic' => $request->has('is_automatic')
                ? $request->boolean('is_automatic')
                : ($coupon?->is_automatic ?? false),
            'target' => $request->input('target', $coupon
                ? ($coupon->target_type === 'all' ? 'all' : $coupon->target_type.':'.$coupon->target_id)
                : 'all'),
        ]);

        $isAutomatic = $request->boolean('is_automatic');
        $discountType = $request->input('discount_type');
        $validated = $request->validate([
            'code' => [Rule::requiredIf(! $isAutomatic), 'nullable', 'string', 'max:40', 'regex:/\A[A-Za-z0-9][A-Za-z0-9_-]*\z/', $uniqueCode],
            'is_automatic' => ['required', 'boolean'],
            'target' => ['required', 'string', 'max:40', 'regex:/\A(?:all|(?:category|product|variant):[1-9][0-9]*)\z/'],
            'discount_type' => ['required', Rule::in(['fixed', 'percentage', 'free_shipping'])],
            'discount_value' => [
                'required',
                'numeric',
                'min:0',
                Rule::when($discountType !== 'free_shipping', ['gt:0']),
                Rule::when($discountType === 'percentage', ['lte:100']),
            ],
            'minimum_subtotal' => ['required', 'numeric', 'min:0'],
            'maximum_discount' => ['nullable', 'numeric', 'gt:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($isAutomatic && filled($validated['code'] ?? null)) {
            throw ValidationException::withMessages(['code' => 'Automatic offers cannot have a coupon code.']);
        }

        [$targetType, $targetId] = $validated['target'] === 'all'
            ? ['all', null]
            : explode(':', $validated['target'], 2);

        $targetExists = match ($targetType) {
            'category' => Category::query()->whereKey($targetId)->where('is_active', true)->exists(),
            'product' => Product::query()->whereKey($targetId)->where('is_active', true)->exists(),
            'variant' => ProductVariant::query()->whereKey($targetId)->where('is_active', true)
                ->whereHas('product', fn ($query) => $query->where('is_active', true))->exists(),
            default => true,
        };

        if (! $targetExists) {
            throw ValidationException::withMessages(['target' => 'Choose an active category, product, or variant.']);
        }

        $startsAt = filled($validated['starts_at'] ?? null) ? $validated['starts_at'] : null;
        $expiresAt = filled($validated['expires_at'] ?? null) ? $validated['expires_at'] : null;

        if ($startsAt && $expiresAt
            && now()->parse($expiresAt)->lessThanOrEqualTo(now()->parse($startsAt))) {
            throw ValidationException::withMessages(['expires_at' => 'The expiry must be later than the start time.']);
        }

        $validated['starts_at'] = $startsAt;
        $validated['expires_at'] = $expiresAt;
        $validated['code'] = filled($validated['code'] ?? null) ? strtoupper(trim($validated['code'])) : null;
        $validated['target_type'] = $targetType;
        $validated['target_id'] = $targetId;
        unset($validated['target']);

        if ($discountType === 'free_shipping') {
            $validated['discount_value'] = 0;
            $validated['maximum_discount'] = null;
        }

        return $validated;
    }

    /**
     * @param  iterable<Coupon>  $coupons
     * @return array<int, string|null>
     */
    private function resolveTargetLabels(iterable $coupons): array
    {
        $categoryIds = [];
        $productIds = [];
        $variantIds = [];

        foreach ($coupons as $coupon) {
            if ($coupon->target_type === 'category' && $coupon->target_id) {
                $categoryIds[] = $coupon->target_id;
            } elseif ($coupon->target_type === 'product' && $coupon->target_id) {
                $productIds[] = $coupon->target_id;
            } elseif ($coupon->target_type === 'variant' && $coupon->target_id) {
                $variantIds[] = $coupon->target_id;
            }
        }

        $categories = ! empty($categoryIds)
            ? Category::query()->whereIn('id', array_unique($categoryIds))->pluck('name', 'id')->all()
            : [];

        $products = ! empty($productIds)
            ? Product::query()->whereIn('id', array_unique($productIds))->pluck('name', 'id')->all()
            : [];

        $variants = ! empty($variantIds)
            ? ProductVariant::query()
                ->with(['product:id,name', 'attributeValues.productAttribute'])
                ->whereIn('id', array_unique($variantIds))
                ->get()
                ->keyBy('id')
                ->map(fn (ProductVariant $variant): string => $variant->product->name.' - '.$variant->displayName())
                ->all()
            : [];

        $labels = [];

        foreach ($coupons as $coupon) {
            $labels[$coupon->id] = match ($coupon->target_type) {
                'category' => $categories[$coupon->target_id] ?? null,
                'product' => $products[$coupon->target_id] ?? null,
                'variant' => $variants[$coupon->target_id] ?? null,
                default => null,
            };
        }

        return $labels;
    }

    private function resolveSingleTargetLabel(?string $target): ?string
    {
        if (! is_string($target) || ! preg_match('/^(category|product|variant):([1-9][0-9]*)$/', $target, $matches)) {
            return null;
        }

        $targetId = (int) $matches[2];

        return match ($matches[1]) {
            'category' => Category::query()->whereKey($targetId)->value('name'),
            'product' => Product::query()->whereKey($targetId)->value('name'),
            'variant' => ProductVariant::query()
                ->with(['product:id,name', 'attributeValues.productAttribute'])
                ->whereKey($targetId)
                ->first()
                ?->pipe(fn (ProductVariant $variant): string => $variant->product->name.' - '.$variant->displayName()),
            default => null,
        };
    }
}
