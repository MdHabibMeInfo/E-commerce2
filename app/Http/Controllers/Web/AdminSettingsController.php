<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HeroBanner;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\SecurityEventService;
use App\Services\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminSettingsController extends Controller
{
    public function edit(Request $request, SiteSettings $settings): View
    {
        $user = $request->user();
        abort_unless(
            $user?->is_system_owner || $user?->can('settings.view') || $user?->can('settings.update'),
            403,
        );

        $branding = $settings->branding();
        $sensitiveSettings = null;

        if ($user->is_system_owner) {
            $legacyDeliveryFee = $settings->get('delivery_fee');
            $insideDhakaDeliveryFeeDefault = $legacyDeliveryFee
                ?? config('storefront.delivery_fee_inside_dhaka', config('storefront.delivery_fee', 60.00));
            $outsideDhakaDeliveryFeeDefault = $legacyDeliveryFee
                ?? config('storefront.delivery_fee_outside_dhaka', config('storefront.delivery_fee', 60.00));

            $sensitiveSettings = [
                'bkash_merchant_number' => $settings->get(
                    'bkash_merchant_number',
                    config('payments.mobile_wallets.bkash.merchant_number'),
                ),
                'nagad_merchant_number' => $settings->get(
                    'nagad_merchant_number',
                    config('payments.mobile_wallets.nagad.merchant_number'),
                ),
                'rocket_merchant_number' => $settings->get(
                    'rocket_merchant_number',
                    config('payments.mobile_wallets.rocket.merchant_number'),
                ),
                'about_us_article' => $settings->get('about_us_article', ''),
                'contact_page_content' => $settings->get('contact_page_content', ''),
                'terms_conditions' => $settings->get('terms_conditions', ''),
                'privacy_policy' => $settings->get('privacy_policy', ''),
                'return_refund_policy' => $settings->get('return_refund_policy', ''),
                'whatsapp_number' => $settings->get('whatsapp_number', ''),
                'whatsapp_url' => $settings->get('whatsapp_url', ''),
                'delivery_fee_inside_dhaka' => $settings->get(
                    'delivery_fee_inside_dhaka',
                    $insideDhakaDeliveryFeeDefault,
                ),
                'delivery_fee_outside_dhaka' => $settings->get(
                    'delivery_fee_outside_dhaka',
                    $outsideDhakaDeliveryFeeDefault,
                ),
                'security_login_attempts' => $settings->integer('security_login_attempts'),
                'security_login_lockout_minutes' => $settings->integer('security_login_lockout_minutes'),
                'security_password_min_length' => $settings->integer('security_password_min_length'),
                'session_lifetime' => config('session.lifetime'),
                'session_secure' => config('session.secure'),
                'session_http_only' => config('session.http_only'),
                'session_same_site' => config('session.same_site'),
            ];
        }

        $heroBanners = HeroBanner::query()->orderBy('sort_order')->orderBy('id')->get();
        $heroBannerTargetLabels = $heroBanners->mapWithKeys(fn(HeroBanner $heroBanner): array => [
            $heroBanner->id => $settings->targetLabel($heroBanner->target),
        ]);

        return view('admin.settings.index', compact('branding', 'heroBanners', 'heroBannerTargetLabels', 'sensitiveSettings'));
    }

    public function heroTargetSuggestions(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user?->is_system_owner || $user?->can('settings.view') || $user?->can('settings.update'),
            403,
        );

        $validated = $request->validate([
            'search' => ['required', 'string', 'min:2', 'max:80'],
        ]);
        $term = addcslashes($validated['search'], '\\%_');
        $like = '%' . $term . '%';

        $categories = Category::query()
            ->where('is_active', true)
            ->where(fn($query) => $query
                ->where('name', 'like', $like)
                ->orWhere('slug', 'like', $like))
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name'])
            ->map(fn(Category $category): array => [
                'value' => 'category:' . $category->id,
                'label' => $category->name,
                'type' => 'Category',
            ]);

        $products = Product::query()
            ->where('is_active', true)
            ->where(fn($query) => $query
                ->where('name', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhere('slug', 'like', $like))
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name'])
            ->map(fn(Product $product): array => [
                'value' => 'product:' . $product->id,
                'label' => $product->name,
                'type' => 'Product',
            ]);

        $variants = ProductVariant::query()
            ->where('is_active', true)
            ->where('is_out_of_stock', false)
            ->whereHas('product', fn($query) => $query
                ->where('is_active', true)
                ->where('is_out_of_stock', false)
                ->where('name', 'like', $like))
            ->orWhere(function ($query) use ($like): void {
                $query->where('is_active', true)
                    ->where('is_out_of_stock', false)
                    ->where(function ($variants) use ($like): void {
                        $variants->where('name', 'like', $like)
                            ->orWhere('sku', 'like', $like);
                    })
                    ->whereHas('product', fn($product) => $product
                        ->where('is_active', true)
                        ->where('is_out_of_stock', false));
            })
            ->with(['product:id,name', 'attributeValues.productAttribute'])
            ->orderBy('product_id')
            ->orderBy('id')
            ->limit(6)
            ->get()
            ->map(fn(ProductVariant $variant): array => [
                'value' => 'variant:' . $variant->id,
                'label' => $variant->product->name . ' - ' . $variant->displayName(),
                'type' => 'Variant',
            ]);

        return response()->json([
            'items' => $categories->concat($products)->concat($variants)->values(),
        ]);
    }

    public function updateBranding(Request $request, SiteSettings $settings, SecurityEventService $events): RedirectResponse
    {
        abort_unless($request->user()?->can('settings.update'), 403);

        $validated = $request->validate([
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=2000,max_height=1000'],
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:max_width=4000,max_height=3000'],
            'home_ad_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:max_width=4000,max_height=3000'],
            'home_ad_url' => ['nullable', 'url:http,https', 'max:2048'],
            'hero_title' => ['nullable', 'string', 'max:150'],
            'hero_description' => ['nullable', 'string', 'max:500'],
            'hero_target' => ['nullable', 'string', 'max:40', 'regex:/^(category|product|variant):[1-9][0-9]*$/'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_hero_image' => ['nullable', 'boolean'],
        ]);

        if (
            filled($validated['hero_target'] ?? null)
            && $settings->destinationFor($validated['hero_target']) === null
        ) {
            throw ValidationException::withMessages([
                'hero_target' => 'Choose an active category, product, or available variant.',
            ]);
        }

        $updates = Arr::only($validated, [
            'home_ad_url',
            'hero_title',
            'hero_description',
            'hero_target',
        ]);
        $oldPaths = [
            'logo_path' => $settings->get('logo_path'),
            'hero_image_path' => $settings->get('hero_image_path'),
            'home_ad_image_path' => $settings->get('home_ad_image_path'),
        ];
        $newPaths = [];

        try {
            foreach (
                [
                    'logo' => 'logo_path',
                    'hero_image' => 'hero_image_path',
                    'home_ad_image' => 'home_ad_image_path',
                ] as $input => $settingKey
            ) {
                if ($request->hasFile($input)) {
                    $path = $request->file($input)->store('branding', 'public');

                    if (! is_string($path)) {
                        throw ValidationException::withMessages([
                            $input => 'The image could not be saved. Please try again.',
                        ]);
                    }

                    $newPaths[] = $path;
                    $updates[$settingKey] = $path;
                } elseif ($request->boolean('remove_' . $input)) {
                    $updates[$settingKey] = null;
                }
            }

            $settings->update($updates, (int) $request->user()->getAuthIdentifier());
        } catch (Throwable $exception) {
            foreach ($newPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        foreach ($oldPaths as $key => $path) {
            if ($path && array_key_exists($key, $updates) && $updates[$key] !== $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $events->record($request, $request->user(), 'site_branding_updated', [
            'keys' => array_keys($updates),
        ]);

        return back()->with('success', 'Storefront branding updated.');
    }

    public function updateSensitive(Request $request, SiteSettings $settings, SecurityEventService $events): RedirectResponse
    {
        abort_unless($request->user()?->is_system_owner, 403);

        $validated = $request->validate([
            'bkash_merchant_number' => ['nullable', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'nagad_merchant_number' => ['nullable', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'rocket_merchant_number' => ['nullable', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'delivery_fee_inside_dhaka' => ['required', 'numeric', 'min:0', 'max:100000'],
            'delivery_fee_outside_dhaka' => ['required', 'numeric', 'min:0', 'max:100000'],
            'about_us_article' => ['nullable', 'string', 'max:10000'],
            'contact_page_content' => ['nullable', 'string', 'max:10000'],
            'terms_conditions' => ['nullable', 'string', 'max:30000'],
            'privacy_policy' => ['nullable', 'string', 'max:30000'],
            'return_refund_policy' => ['nullable', 'string', 'max:30000'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'whatsapp_url' => ['nullable', 'url:http,https', 'max:2048'],
            'security_login_attempts' => ['required', 'integer', 'between:3,10'],
            'security_login_lockout_minutes' => ['required', 'integer', 'between:1,60'],
            'security_password_min_length' => ['required', 'integer', 'between:12,32'],
        ]);
        $values = array_map(static fn(mixed $value): ?string => $value === null ? null : (string) $value, $validated);

        $settings->update($values, (int) $request->user()->getAuthIdentifier());
        $events->record($request, $request->user(), 'sensitive_site_settings_updated', [
            'keys' => array_keys($values),
        ]);

        return back()->with('success', 'System owner settings updated.');
    }
}
