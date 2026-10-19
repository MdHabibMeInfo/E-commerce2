<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SiteSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class SiteSettings
{
    private const CACHE_KEY = 'site-settings.encrypted-values.v1';

    /** @var array<string, mixed> */
    private const DEFAULTS = [
        'logo_path' => null,
        'hero_image_path' => null,
        'home_ad_image_path' => null,
        'home_ad_url' => null,
        'hero_title' => 'Good Food for a Better You',
        'hero_description' => 'Discover fresh, high-quality products that bring health and happiness to your everyday life.',
        'hero_target' => null,
        'about_us_article' => '',
        'contact_page_content' => '',
        'terms_conditions' => '',
        'privacy_policy' => '',
        'return_refund_policy' => '',
        'whatsapp_number' => null,
        'whatsapp_url' => null,
        'security_login_attempts' => '5',
        'security_login_lockout_minutes' => '1',
        'security_password_min_length' => '12',
    ];

    private const ALLOWED_KEYS = [
        'logo_path',
        'hero_image_path',
        'home_ad_image_path',
        'home_ad_url',
        'hero_title',
        'hero_description',
        'hero_target',
        'about_us_article',
        'contact_page_content',
        'terms_conditions',
        'privacy_policy',
        'return_refund_policy',
        'whatsapp_number',
        'whatsapp_url',
        'bkash_merchant_number',
        'nagad_merchant_number',
        'rocket_merchant_number',
        'delivery_fee',
        'delivery_fee_inside_dhaka',
        'delivery_fee_outside_dhaka',
        'security_login_attempts',
        'security_login_lockout_minutes',
        'security_password_min_length',
    ];

    /** @return array<string, mixed> */
    public function all(): array
    {
        $encryptedValues = Cache::rememberForever(self::CACHE_KEY, static function (): array {
            return SiteSetting::query()
                ->get(['key', 'value'])
                ->mapWithKeys(fn(SiteSetting $setting): array => [$setting->key => $setting->value])
                ->all();
        });

        $values = [];

        foreach ($encryptedValues as $key => $value) {
            $values[$key] = $value === null ? null : Crypt::decryptString($value);
        }

        return array_replace(self::DEFAULTS, $values);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    public function logoUrl(): string
    {
        $logoPath = $this->get('logo_path');

        return is_string($logoPath) && $logoPath !== ''
            ? Storage::disk('public')->url($logoPath)
            : asset('images/brand/ziyanmart-logo.png');
    }

    public function boolean(string $key): bool
    {
        return filter_var($this->get($key, false), FILTER_VALIDATE_BOOLEAN);
    }

    public function integer(string $key): int
    {
        return (int) $this->get($key, 0);
    }

    /** @return array<string, mixed> */
    public function branding(): array
    {
        return Arr::only($this->all(), [
            'logo_path',
            'hero_image_path',
            'home_ad_image_path',
            'home_ad_url',
            'hero_title',
            'hero_description',
            'hero_target',
            'about_us_article',
            'whatsapp_number',
            'whatsapp_url',
        ]);
    }

    public function heroDestination(): ?string
    {
        $target = $this->get('hero_target');

        if (! is_string($target)) {
            return null;
        }

        return $this->destinationFor($target);
    }

    public function heroTargetLabel(): ?string
    {
        $target = $this->get('hero_target');

        return is_string($target) ? $this->targetLabel($target) : null;
    }

    public function targetLabel(?string $target): ?string
    {
        if (! is_string($target) || ! preg_match('/^(category|product|variant):([1-9][0-9]*)$/', $target, $matches)) {
            return null;
        }

        $targetId = (int) $matches[2];

        return match ($matches[1]) {
            'category' => Category::query()
                ->whereKey($targetId)
                ->where('is_active', true)
                ->value('name'),
            'product' => Product::query()
                ->whereKey($targetId)
                ->where('is_active', true)
                ->value('name'),
            'variant' => $this->variantLabel($targetId),
        };
    }

    public function destinationFor(string $target): ?string
    {
        if (! preg_match('/^(category|product|variant):([1-9][0-9]*)$/', $target, $matches)) {
            return null;
        }

        $targetId = (int) $matches[2];

        return match ($matches[1]) {
            'category' => $this->categoryDestination($targetId),
            'product' => $this->productDestination($targetId),
            'variant' => $this->variantDestination($targetId),
        };
    }

    private function categoryDestination(int $categoryId): ?string
    {
        $slug = Category::query()
            ->whereKey($categoryId)
            ->where('is_active', true)
            ->value('slug');

        return $slug ? route('shop', ['category' => $slug]) : null;
    }

    private function productDestination(int $productId): ?string
    {
        $slug = Product::query()
            ->whereKey($productId)
            ->where('is_active', true)
            ->value('slug');

        return $slug ? route('products.show', ['product' => $slug]) : null;
    }

    private function variantDestination(int $variantId): ?string
    {
        $variant = ProductVariant::query()
            ->whereKey($variantId)
            ->where('is_active', true)
            ->where('is_out_of_stock', false)
            ->whereHas('product', fn($query) => $query
                ->where('is_active', true)
                ->where('is_out_of_stock', false))
            ->with('product:id,slug')
            ->first();

        return $variant
            ? route('products.show', ['product' => $variant->product->slug, 'variant' => $variant->getKey()])
            : null;
    }

    private function variantLabel(int $variantId): ?string
    {
        $variant = ProductVariant::query()
            ->whereKey($variantId)
            ->where('is_active', true)
            ->where('is_out_of_stock', false)
            ->whereHas('product', fn($query) => $query
                ->where('is_active', true)
                ->where('is_out_of_stock', false))
            ->with(['product:id,name', 'attributeValues.productAttribute'])
            ->first();

        return $variant ? $variant->product->name . ' - ' . $variant->displayName() : null;
    }

    /** @param array<string, string|null> $values */
    public function update(array $values, int $updatedBy): void
    {
        foreach (array_keys($values) as $key) {
            if (! in_array($key, self::ALLOWED_KEYS, true)) {
                throw new InvalidArgumentException('Unsupported site setting key.');
            }
        }

        DB::transaction(function () use ($values, $updatedBy): void {
            foreach ($values as $key => $value) {
                SiteSetting::query()->updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value === null ? null : Crypt::encryptString($value),
                        'updated_by' => $updatedBy,
                    ],
                );
            }
        });

        Cache::forget(self::CACHE_KEY);
    }
}
