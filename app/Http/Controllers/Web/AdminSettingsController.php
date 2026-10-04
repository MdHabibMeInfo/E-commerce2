<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
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
        $heroTargetLabel = $settings->heroTargetLabel();
        $sensitiveSettings = null;

        if ($user->is_system_owner) {
            $sensitiveSettings = [
                'bkash_merchant_number' => $settings->get(
                    'bkash_merchant_number',
                    config('payments.mobile_wallets.bkash.merchant_number'),
                ),
                'nagad_merchant_number' => $settings->get(
                    'nagad_merchant_number',
                    config('payments.mobile_wallets.nagad.merchant_number'),
                ),
                'security_admin_2fa_required' => $settings->boolean('security_admin_2fa_required'),
                'security_login_attempts' => $settings->integer('security_login_attempts'),
                'security_login_lockout_minutes' => $settings->integer('security_login_lockout_minutes'),
                'security_password_min_length' => $settings->integer('security_password_min_length'),
                'session_lifetime' => config('session.lifetime'),
                'session_secure' => config('session.secure'),
                'session_http_only' => config('session.http_only'),
                'session_same_site' => config('session.same_site'),
            ];
        }

        return view('admin.settings.index', compact('branding', 'heroTargetLabel', 'sensitiveSettings'));
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
        $like = '%'.$term.'%';

        $categories = Category::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query
                ->where('name', 'like', $like)
                ->orWhere('slug', 'like', $like))
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
            ->where(fn ($query) => $query
                ->where('name', 'like', $like)
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
            ->where('is_out_of_stock', false)
            ->whereHas('product', fn ($query) => $query
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
                    ->whereHas('product', fn ($product) => $product
                        ->where('is_active', true)
                        ->where('is_out_of_stock', false));
            })
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
            'hero_title' => ['required', 'string', 'max:150'],
            'hero_description' => ['required', 'string', 'max:500'],
            'hero_target' => ['nullable', 'string', 'max:40', 'regex:/^(category|product|variant):[1-9][0-9]*$/'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_hero_image' => ['nullable', 'boolean'],
        ]);

        if (filled($validated['hero_target'] ?? null)
            && $settings->destinationFor($validated['hero_target']) === null) {
            throw ValidationException::withMessages([
                'hero_target' => 'Choose an active category, product, or available variant.',
            ]);
        }

        $updates = Arr::only($validated, [
            'hero_title',
            'hero_description',
            'hero_target',
        ]);
        $oldPaths = [
            'logo_path' => $settings->get('logo_path'),
            'hero_image_path' => $settings->get('hero_image_path'),
        ];
        $newPaths = [];

        try {
            foreach ([
                'logo' => 'logo_path',
                'hero_image' => 'hero_image_path',
            ] as $input => $settingKey) {
                if ($request->hasFile($input)) {
                    $path = $request->file($input)->store('branding', 'public');

                    if (! is_string($path)) {
                        throw ValidationException::withMessages([
                            $input => 'The image could not be saved. Please try again.',
                        ]);
                    }

                    $newPaths[] = $path;
                    $updates[$settingKey] = $path;
                } elseif ($request->boolean('remove_'.$input)) {
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
            'security_admin_2fa_required' => ['required', 'boolean'],
            'security_login_attempts' => ['required', 'integer', 'between:3,10'],
            'security_login_lockout_minutes' => ['required', 'integer', 'between:1,60'],
            'security_password_min_length' => ['required', 'integer', 'between:12,32'],
        ]);
        $validated['security_admin_2fa_required'] = $validated['security_admin_2fa_required'] ? '1' : '0';
        $values = array_map(static fn (mixed $value): ?string => $value === null ? null : (string) $value, $validated);

        $settings->update($values, (int) $request->user()->getAuthIdentifier());
        $events->record($request, $request->user(), 'sensitive_site_settings_updated', [
            'keys' => array_keys($values),
        ]);

        return back()->with('success', 'Payment and security settings updated.');
    }
}
