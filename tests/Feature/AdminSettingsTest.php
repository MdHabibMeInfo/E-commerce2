<?php

use App\Models\Category;
use App\Models\HeroBanner;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SecurityEvent;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('lets a settings editor update branding but denies sensitive settings', function () {
    Storage::fake('public');
    $category = Category::create([
        'name' => 'Hero Advert Category',
        'slug' => 'hero-advert-category',
        'is_active' => true,
    ]);
    $editor = User::factory()->create([
        'account_type' => 'admin',
        'status' => 'active',
    ]);
    $permission = Permission::findOrCreate('settings.update', 'web');
    $role = Role::query()->create([
        'name' => 'Branding Editor',
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo($permission);
    $editor->assignRole($role);
    $this->actingAs(completeManagementTwoFactorForTest($editor));

    $this->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('Site Logo')
        ->assertDontSee('name="logo"', false)
        ->assertSee('Homepage Hero Images')
        ->assertDontSee('security_admin_2fa_required', false)
        ->assertDontSee('name="about_us_article"', false)
        ->assertDontSee('name="contact_page_content"', false)
        ->assertDontSee('name="terms_conditions"', false)
        ->assertDontSee('name="privacy_policy"', false)
        ->assertDontSee('name="return_refund_policy"', false)
        ->assertDontSee('name="whatsapp_number"', false)
        ->assertDontSee('name="security_admin_2fa_required"', false)
        ->assertDontSee('bkash_merchant_number', false);

    $this->patch(route('admin.settings.branding.update'), [
        'logo' => UploadedFile::fake()->image('store-logo.png', 300, 120),
    ])->assertForbidden();

    expect(app(SiteSettings::class)->get('logo_path'))->toBeNull();

    $this->patch(route('admin.settings.branding.update'), [
        'hero_title' => 'Updated by a branding editor',
    ])->assertRedirect();

    expect(app(SiteSettings::class)->get('hero_title'))->toBe('Updated by a branding editor');
    $this->get(route('home'))
        ->assertDontSee('data-hero-carousel', false);
    $this->assertDatabaseHas('security_events', ['action' => 'site_branding_updated']);

    $this->patch(route('admin.settings.security.update'), [
        'bkash_merchant_number' => '01700000000',
        'nagad_merchant_number' => '01800000000',
        'contact_page_content' => 'Unauthorized contact content.',
        'terms_conditions' => 'Unauthorized policy edit.',
        'security_admin_2fa_required' => '1',
        'security_login_attempts' => 5,
        'security_login_lockout_minutes' => 1,
        'security_password_min_length' => 12,
    ])->assertForbidden();

    $this->assertDatabaseMissing('site_settings', ['key' => 'bkash_merchant_number']);
    $this->assertDatabaseMissing('site_settings', ['key' => 'contact_page_content']);
    $this->assertDatabaseMissing('site_settings', ['key' => 'terms_conditions']);

    $this->patch(route('admin.settings.branding.update'), [
        'remove_logo' => '1',
    ])->assertForbidden();

    expect(app(SiteSettings::class)->get('logo_path'))->toBeNull();
});

it('uses the system owner managed logo as the site favicon', function () {
    Storage::fake('public');
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $this->actingAs($owner);

    $this->get(route('admin.settings'))
        ->assertSee('name="logo"', false);

    $this->patch(route('admin.settings.branding.update'), [
        'logo' => UploadedFile::fake()->image('store-logo.png', 300, 120),
    ])->assertRedirect();

    $logoPath = app(SiteSettings::class)->get('logo_path');
    Storage::disk('public')->assertExists($logoPath);
    $this->get(route('home'))
        ->assertSee('rel="icon" href="' . Storage::disk('public')->url($logoPath) . '"', false);

    $this->patch(route('admin.settings.branding.update'), [
        'remove_logo' => '1',
    ])->assertRedirect();

    expect(app(SiteSettings::class)->get('logo_path'))->toBeNull();
    $this->get(route('home'))
        ->assertSee('rel="icon" href="' . asset('images/brand/ziyanmart-logo.png') . '"', false);
});

it('shows the homepage side advertisement only when its image and link are configured', function () {
    Storage::fake('public');
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());
    HeroBanner::create([
        'image_path' => 'hero-banners/main.jpg',
        'target' => null,
        'sort_order' => 0,
    ]);

    $this->actingAs($owner)->patch(route('admin.settings.branding.update'), [
        'home_ad_image' => UploadedFile::fake()->image('side-ad.png', 640, 640),
    ])->assertRedirect();

    $adPath = app(SiteSettings::class)->get('home_ad_image_path');
    Storage::disk('public')->assertExists($adPath);
    $this->get(route('home'))
        ->assertSee('data-hero-carousel', false)
        ->assertDontSee('lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]', false);

    $this->patch(route('admin.settings.branding.update'), [
        'home_ad_url' => 'https://example.com/offer',
    ])->assertRedirect();

    $this->get(route('home'))
        ->assertSee('lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]', false)
        ->assertSee('href="https://example.com/offer"', false)
        ->assertSee('pt-4', false)
        ->assertSee('alt="Advertisement"', false)
        ->assertSee('class="h-full w-full object-contain"', false)
        ->assertSee('hidden h-[clamp(250px,25vw,420px)] overflow-hidden bg-[#fbfaf4] lg:block', false);

    $this->patch(route('admin.settings.branding.update'), [
        'remove_home_ad_image' => '1',
    ])->assertRedirect();

    Storage::disk('public')->assertMissing($adPath);
    $this->get(route('home'))
        ->assertDontSee('lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]', false);
});

it('lets the system owner update encrypted payment and security settings', function () {
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $this->actingAs($owner);

    $this->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('System Owner Only')
        ->assertSee('name="bkash_merchant_number"', false)
        ->assertSee('name="rocket_merchant_number"', false)
        ->assertSee('name="about_us_article"', false)
        ->assertSee('name="contact_page_content"', false)
        ->assertSee('name="terms_conditions"', false)
        ->assertSee('name="privacy_policy"', false)
        ->assertSee('name="return_refund_policy"', false)
        ->assertSee('name="whatsapp_number"', false)
        ->assertSee('name="delivery_fee_inside_dhaka"', false)
        ->assertSee('name="delivery_fee_outside_dhaka"', false)
        ->assertSee('Authenticator verification is always required for management accounts.');

    $this->patch(route('admin.settings.security.update'), [
        'bkash_merchant_number' => '01700000000',
        'nagad_merchant_number' => '01800000000',
        'rocket_merchant_number' => '01900000003',
        'delivery_fee_inside_dhaka' => '45.50',
        'delivery_fee_outside_dhaka' => '85.50',
        'about_us_article' => 'Our story starts with a promise of quality.',
        'contact_page_content' => 'Reach our team every day for order support.',
        'terms_conditions' => "Orders are subject to these terms.\nTerms are updated by the store owner.",
        'privacy_policy' => 'Customer data is used to fulfill orders.',
        'return_refund_policy' => 'Contact us within 3 days about damaged items.',
        'whatsapp_number' => '8801712345678',
        'whatsapp_url' => 'https://wa.me/8801712345678',
        'security_login_attempts' => 7,
        'security_login_lockout_minutes' => 15,
        'security_password_min_length' => 16,
    ])->assertRedirect();

    $encryptedMerchantNumber = SiteSetting::query()->where('key', 'bkash_merchant_number')->value('value');
    expect($encryptedMerchantNumber)
        ->not->toBe('01700000000')
        ->and(Crypt::decryptString($encryptedMerchantNumber))->toBe('01700000000')
        ->and(app(SiteSettings::class)->get('rocket_merchant_number'))->toBe('01900000003')
        ->and(app(SiteSettings::class)->get('delivery_fee_inside_dhaka'))->toBe('45.50')
        ->and(app(SiteSettings::class)->get('delivery_fee_outside_dhaka'))->toBe('85.50')
        ->and(app(SiteSettings::class)->get('about_us_article'))->toBe('Our story starts with a promise of quality.')
        ->and(app(SiteSettings::class)->get('contact_page_content'))->toBe('Reach our team every day for order support.')
        ->and(app(SiteSettings::class)->get('terms_conditions'))->toBe("Orders are subject to these terms.\nTerms are updated by the store owner.")
        ->and(app(SiteSettings::class)->get('privacy_policy'))->toBe('Customer data is used to fulfill orders.')
        ->and(app(SiteSettings::class)->get('return_refund_policy'))->toBe('Contact us within 3 days about damaged items.')
        ->and(app(SiteSettings::class)->get('whatsapp_number'))->toBe('8801712345678')
        ->and(app(SiteSettings::class)->get('whatsapp_url'))->toBe('https://wa.me/8801712345678')
        ->and(app(SiteSettings::class)->integer('security_login_attempts'))->toBe(7)
        ->and(app(SiteSettings::class)->integer('security_password_min_length'))->toBe(16);
    $this->assertDatabaseHas('security_events', ['action' => 'sensitive_site_settings_updated']);
    expect(SecurityEvent::query()->where('action', 'sensitive_site_settings_updated')->value('context'))
        ->toHaveKey('keys');
});

it('rejects arbitrary hero destinations without changing the saved branding', function () {
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());

    $this->actingAs($owner)
        ->from(route('admin.settings'))
        ->patch(route('admin.settings.branding.update'), [
            'hero_title' => 'Unsafe link test',
            'hero_description' => 'Description',
            'hero_target' => 'https://external.example/redirect',
        ])
        ->assertSessionHasErrors('hero_target');

    expect(app(SiteSettings::class)->get('hero_title'))->toBe('Good Food for a Better You');
});

it('resolves only active internal category product and variant targets', function () {
    $category = Category::create([
        'name' => 'Target Category',
        'slug' => 'target-category',
        'is_active' => true,
    ]);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Target Variant Product',
        'slug' => 'target-variant-product',
        'price' => 100,
        'stock' => 10,
        'type' => 'variable',
        'is_active' => true,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Large',
        'sku' => 'HERO-TARGET-LARGE',
        'combination_hash' => str_repeat('a', 64),
        'price' => 120,
        'stock' => 5,
        'is_active' => true,
        'is_out_of_stock' => false,
    ]);
    $settings = app(SiteSettings::class);

    expect($settings->destinationFor('category:' . $category->id))
        ->toBe(route('shop', ['category' => $category->slug]))
        ->and($settings->destinationFor('product:' . $product->id))
        ->toBe(route('products.show', ['product' => $product->slug]))
        ->and($settings->destinationFor('variant:' . $variant->id))
        ->toBe(route('products.show', ['product' => $product->slug, 'variant' => $variant->id]))
        ->and($settings->destinationFor('https://external.example'))
        ->toBeNull();

    $this->get(route('products.show', ['product' => $product->slug, 'variant' => $variant->id]))
        ->assertOk()
        ->assertSee('value="' . $variant->id . '"', false)
        ->assertSee('selected', false);
});

it('searches active categories products and available variants for hero destinations', function () {
    $category = Category::create([
        'name' => 'Searchable Campaign Category',
        'slug' => 'searchable-campaign-category',
        'is_active' => true,
    ]);
    Category::create([
        'name' => 'Searchable Hidden Category',
        'slug' => 'searchable-hidden-category',
        'is_active' => false,
    ]);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Searchable Campaign Product',
        'slug' => 'searchable-campaign-product',
        'price' => 100,
        'stock' => 10,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $variantProduct = Product::create([
        'category_id' => $category->id,
        'name' => 'Variant Campaign Item',
        'slug' => 'variant-campaign-item',
        'price' => 100,
        'stock' => 10,
        'type' => 'variable',
        'is_active' => true,
    ]);
    ProductVariant::create([
        'product_id' => $variantProduct->id,
        'name' => 'Searchable Campaign Variant',
        'sku' => 'SEARCH-CAMPAIGN-VARIANT',
        'combination_hash' => str_repeat('c', 64),
        'price' => 120,
        'stock' => 3,
        'is_active' => true,
        'is_out_of_stock' => false,
    ]);
    ProductVariant::create([
        'product_id' => $variantProduct->id,
        'name' => 'Searchable Sold Out Variant',
        'sku' => 'SEARCH-SOLD-OUT-VARIANT',
        'combination_hash' => str_repeat('d', 64),
        'price' => 120,
        'stock' => 0,
        'is_active' => true,
        'is_out_of_stock' => true,
    ]);
    $this->actingAs(completeManagementTwoFactorForTest(createSystemOwnerUser()));

    $categoryResponse = $this->getJson(route('admin.settings.hero-targets', ['search' => 'Searchable Campaign Category']));
    $productResponse = $this->getJson(route('admin.settings.hero-targets', ['search' => 'Searchable Campaign Product']));
    $variantResponse = $this->getJson(route('admin.settings.hero-targets', ['search' => 'SEARCH-CAMPAIGN-VARIANT']));

    $categoryResponse->assertOk()->assertJsonPath('items.0.value', 'category:' . $category->id);
    $productResponse->assertOk()->assertJsonPath('items.0.value', 'product:' . $product->id);
    $variantResponse->assertOk()->assertJsonPath('items.0.value', 'variant:' . ProductVariant::query()
        ->where('sku', 'SEARCH-CAMPAIGN-VARIANT')
        ->value('id'));
    expect($categoryResponse->json('items'))->toHaveCount(1)
        ->and($productResponse->json('items'))->toHaveCount(1)
        ->and($variantResponse->json('items'))->toHaveCount(1);
});

it('does not render an automatic hero when no banner images were added', function () {
    Product::create([
        'name' => 'Automatic Hero Must Not Appear',
        'slug' => 'automatic-hero-must-not-appear',
        'price' => 125,
        'stock' => 8,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $this->get(route('home'))
        ->assertDontSee('data-hero-carousel', false)
        ->assertDontSee('data-hero-slide', false);

    $this->assertDatabaseCount('hero_banners', 0);
});

it('allows a settings editor to add update order and delete hero images', function () {
    Storage::fake('public');
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $this->actingAs($owner);

    $this->get(route('home'))->assertDontSee('data-hero-carousel', false);

    $this->post(route('admin.settings.hero-banners.store'), [
        'image' => UploadedFile::fake()->image('hero-first.png', 1200, 600),
        'sort_order' => 4,
    ])->assertRedirect();
    $this->post(route('admin.settings.hero-banners.store'), [
        'image' => UploadedFile::fake()->image('hero-second.png', 1200, 600),
        'sort_order' => 1,
    ])->assertRedirect();

    $firstBanner = HeroBanner::query()->where('sort_order', 4)->firstOrFail();
    $secondBanner = HeroBanner::query()->where('sort_order', 1)->firstOrFail();
    $firstPath = $firstBanner->image_path;
    $secondPath = $secondBanner->image_path;
    Storage::disk('public')->assertExists([$firstPath, $secondPath]);

    $response = $this->get(route('home'))
        ->assertSee(Storage::disk('public')->url($secondPath))
        ->assertSee(Storage::disk('public')->url($firstPath))
        ->assertSee('class="h-full w-full object-cover"', false)
        ->assertDontSee('Fresh · Natural · Organic')
        ->assertSee('data-hero-pagination', false);
    expect(strpos($response->getContent(), Storage::disk('public')->url($secondPath)))
        ->toBeLessThan(strpos($response->getContent(), Storage::disk('public')->url($firstPath)));

    $this->put(route('admin.settings.hero-banners.update', $secondBanner), [
        'image' => UploadedFile::fake()->image('hero-replacement.png', 1400, 700),
        'sort_order' => 7,
    ])->assertRedirect();

    $secondBanner->refresh();
    Storage::disk('public')->assertMissing($secondPath);
    Storage::disk('public')->assertExists($secondBanner->image_path);
    expect($secondBanner->sort_order)->toBe(7);

    $this->delete(route('admin.settings.hero-banners.destroy', $firstBanner))->assertRedirect();

    $this->assertDatabaseMissing('hero_banners', ['id' => $firstBanner->id]);
    Storage::disk('public')->assertMissing($firstPath);
    $this->assertDatabaseHas('security_events', ['action' => 'hero_banner_created']);
    $this->assertDatabaseHas('security_events', ['action' => 'hero_banner_updated']);
    $this->assertDatabaseHas('security_events', ['action' => 'hero_banner_deleted']);
});

it('forbids users without settings permission from managing hero images', function () {
    $user = User::factory()->create([
        'account_type' => 'admin',
        'status' => 'active',
    ]);
    $this->actingAs(completeManagementTwoFactorForTest($user))
        ->post(route('admin.settings.hero-banners.store'), [
            'image' => UploadedFile::fake()->image('unauthorized-hero.png'),
        ])
        ->assertForbidden();
});

it('links hero images to selected categories products and variants or the homepage', function () {
    Storage::fake('public');
    $category = Category::create([
        'name' => 'Hero Destination Category',
        'slug' => 'hero-destination-category',
        'is_active' => true,
    ]);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Hero Destination Product',
        'slug' => 'hero-destination-product',
        'price' => 100,
        'stock' => 10,
        'type' => 'variable',
        'is_active' => true,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Large',
        'sku' => 'HERO-DESTINATION-LARGE',
        'combination_hash' => str_repeat('f', 64),
        'price' => 120,
        'stock' => 5,
        'is_active' => true,
        'is_out_of_stock' => false,
    ]);
    $this->actingAs(completeManagementTwoFactorForTest(createSystemOwnerUser()));

    foreach (
        [
            ['hero-category.png', 'category:' . $category->id],
            ['hero-product.png', 'product:' . $product->id],
            ['hero-variant.png', 'variant:' . $variant->id],
            ['hero-home.png', null],
        ] as [$filename, $target]
    ) {
        $this->post(route('admin.settings.hero-banners.store'), array_filter([
            'image' => UploadedFile::fake()->image($filename, 1200, 600),
            'target' => $target,
        ], static fn(mixed $value): bool => $value !== null))->assertRedirect();
    }

    $settingsResponse = $this->get(route('admin.settings'))
        ->assertSee('hero-target-search-new', false)
        ->assertSee('Hero Destination Category')
        ->assertSee('Hero Destination Product');
    expect(substr_count($settingsResponse->getContent(), 'data-hero-target-search'))
        ->toBe(5)
        ->and(substr_count($settingsResponse->getContent(), 'data-clear-hero-target'))
        ->toBe(5);

    $response = $this->get(route('home'));
    $html = preg_replace('/\s+/', ' ', $response->getContent());

    expect($html)
        ->toContain('data-hero-pagination')
        ->toContain('data-hero-dot="0"')
        ->toContain('data-hero-dot="3"')
        ->toContain('object-contain')
        ->toContain('<a href="' . route('shop', ['category' => $category->slug]) . '" aria-label="Open selected destination"')
        ->toContain('<a href="' . route('products.show', ['product' => $product->slug]) . '" aria-label="Open selected destination"')
        ->toContain('<a href="' . route('products.show', ['product' => $product->slug, 'variant' => $variant->id]) . '" aria-label="Open selected destination"')
        ->toContain('<a href="' . route('home') . '" aria-label="Open homepage"');
});

it('rejects an external hero image destination without saving the image', function () {
    Storage::fake('public');
    $this->actingAs(completeManagementTwoFactorForTest(createSystemOwnerUser()))
        ->from(route('admin.settings'))
        ->post(route('admin.settings.hero-banners.store'), [
            'image' => UploadedFile::fake()->image('external-hero.png'),
            'target' => 'https://external.example',
        ])
        ->assertSessionHasErrors('target');

    $this->assertDatabaseCount('hero_banners', 0);
    Storage::disk('public')->assertDirectoryEmpty('hero-banners');
});

it('applies the configured password minimum to customer registration', function () {
    $owner = createSystemOwnerUser();
    app(SiteSettings::class)->update([
        'security_password_min_length' => '16',
    ], $owner->id);

    $this->post(route('register.store'), [
        'name' => 'Password Policy Customer',
        'email' => 'password-policy@example.com',
        'phone' => '01712345678',
        'password' => 'StrongAbc1!xy',
        'password_confirmation' => 'StrongAbc1!xy',
    ])->assertSessionHasErrors('password');

    $this->assertDatabaseMissing('users', ['email' => 'password-policy@example.com']);
});

it('applies the configured lockout after the allowed failed admin logins', function () {
    $owner = createSystemOwnerUser(['email' => 'lockout-owner@example.com']);
    app(SiteSettings::class)->update([
        'security_login_attempts' => '3',
        'security_login_lockout_minutes' => '5',
    ], $owner->id);
    Notification::fake();

    foreach (range(1, 3) as $attempt) {
        $this->post(route('login.store'), [
            'email' => $owner->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    $this->post(route('login.store'), [
        'email' => $owner->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    expect(session('errors')->getBag('default')->first('email'))->toContain('Too many login attempts');
});

it('always requires an authenticator for management access', function () {
    $owner = createSystemOwnerUser(['email' => 'two-factor-policy@example.com']);
    SiteSetting::query()->updateOrCreate(
        ['key' => 'security_admin_2fa_required'],
        ['value' => Crypt::encryptString('0')],
    );
    Notification::fake();

    $this->actingAs($owner)
        ->get(route('admin'))
        ->assertRedirect(route('admin.two-factor.setup'));
    $this->get(route('account.profile'))
        ->assertRedirect(route('admin.two-factor.setup'));
});
