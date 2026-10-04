<?php

use App\Models\Category;
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
        ->assertSee('Logo & Homepage Hero')
        ->assertDontSee('security_admin_2fa_required', false)
        ->assertDontSee('bkash_merchant_number', false);

    $this->patch(route('admin.settings.branding.update'), [
        'logo' => UploadedFile::fake()->image('store-logo.png', 300, 120),
        'hero_image' => UploadedFile::fake()->image('store-hero.png', 900, 700),
        'hero_title' => 'Fresh food, delivered',
        'hero_description' => 'Everyday essentials, delivered with care.',
        'hero_target' => 'category:'.$category->id,
    ])->assertRedirect();

    $logoPath = app(SiteSettings::class)->get('logo_path');
    $heroImagePath = app(SiteSettings::class)->get('hero_image_path');
    Storage::disk('public')->assertExists($logoPath);
    Storage::disk('public')->assertExists($heroImagePath);
    expect(app(SiteSettings::class)->get('hero_title'))->toBe('Fresh food, delivered');
    $this->get(route('home'))
        ->assertSee('Fresh food, delivered')
        ->assertSee('href="'.route('shop', ['category' => $category->slug]).'"', false);
    $this->assertDatabaseHas('security_events', ['action' => 'site_branding_updated']);

    $this->patch(route('admin.settings.security.update'), [
        'bkash_merchant_number' => '01700000000',
        'nagad_merchant_number' => '01800000000',
        'security_admin_2fa_required' => '1',
        'security_login_attempts' => 5,
        'security_login_lockout_minutes' => 1,
        'security_password_min_length' => 12,
    ])->assertForbidden();

    $this->assertDatabaseMissing('site_settings', ['key' => 'bkash_merchant_number']);

    $this->patch(route('admin.settings.branding.update'), [
        'remove_logo' => '1',
        'remove_hero_image' => '1',
        'hero_title' => 'Fresh food, delivered',
        'hero_description' => 'Everyday essentials, delivered with care.',
        'hero_target' => '',
    ])->assertRedirect();

    Storage::disk('public')->assertMissing($logoPath);
    Storage::disk('public')->assertMissing($heroImagePath);
    expect(app(SiteSettings::class)->get('logo_path'))->toBeNull();
    expect(app(SiteSettings::class)->get('hero_image_path'))->toBeNull();
});

it('lets the system owner update encrypted payment and security settings', function () {
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $this->actingAs($owner);

    $this->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('System Owner Only')
        ->assertSee('name="bkash_merchant_number"', false);

    $this->patch(route('admin.settings.security.update'), [
        'bkash_merchant_number' => '01700000000',
        'nagad_merchant_number' => '01800000000',
        'security_admin_2fa_required' => '1',
        'security_login_attempts' => 7,
        'security_login_lockout_minutes' => 15,
        'security_password_min_length' => 16,
    ])->assertRedirect();

    $encryptedMerchantNumber = SiteSetting::query()->where('key', 'bkash_merchant_number')->value('value');
    expect($encryptedMerchantNumber)
        ->not->toBe('01700000000')
        ->and(Crypt::decryptString($encryptedMerchantNumber))->toBe('01700000000')
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

    expect($settings->destinationFor('category:'.$category->id))
        ->toBe(route('shop', ['category' => $category->slug]))
        ->and($settings->destinationFor('product:'.$product->id))
        ->toBe(route('products.show', ['product' => $product->slug]))
        ->and($settings->destinationFor('variant:'.$variant->id))
        ->toBe(route('products.show', ['product' => $product->slug, 'variant' => $variant->id]))
        ->and($settings->destinationFor('https://external.example'))
        ->toBeNull();

    $this->get(route('products.show', ['product' => $product->slug, 'variant' => $variant->id]))
        ->assertOk()
        ->assertSee('value="'.$variant->id.'"', false)
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

    $categoryResponse->assertOk()->assertJsonPath('items.0.value', 'category:'.$category->id);
    $productResponse->assertOk()->assertJsonPath('items.0.value', 'product:'.$product->id);
    $variantResponse->assertOk()->assertJsonPath('items.0.value', 'variant:'.ProductVariant::query()
        ->where('sku', 'SEARCH-CAMPAIGN-VARIANT')
        ->value('id'));
    expect($categoryResponse->json('items'))->toHaveCount(1)
        ->and($productResponse->json('items'))->toHaveCount(1)
        ->and($variantResponse->json('items'))->toHaveCount(1);
});

it('renders a custom hero image without a link when no target is selected', function () {
    Storage::fake('public');
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());
    Product::create([
        'name' => 'Second Hero Carousel Slide',
        'slug' => 'second-hero-carousel-slide',
        'price' => 125,
        'stock' => 8,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $this->actingAs($owner)->patch(route('admin.settings.branding.update'), [
        'hero_image' => UploadedFile::fake()->image('image-only-hero.png', 900, 700),
        'hero_title' => 'Image only advertisement',
        'hero_description' => 'A custom campaign image.',
        'hero_target' => '',
    ])->assertRedirect();

    $heroImageUrl = Storage::disk('public')->url(app(SiteSettings::class)->get('hero_image_path'));

    $this->get(route('home'))
        ->assertSee('Image only advertisement')
        ->assertSee($heroImageUrl)
        ->assertSee('<a href="'.route('home').'" aria-label="Image only advertisement"', false)
        ->assertSee('data-hero-next', false)
        ->assertDontSee('Shop now')
        ->assertDontSee('<a href="'.$heroImageUrl.'"', false);
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

it('lets the system owner disable the admin authenticator requirement', function () {
    $owner = createSystemOwnerUser(['email' => 'two-factor-policy@example.com']);
    app(SiteSettings::class)->update([
        'security_admin_2fa_required' => '0',
    ], $owner->id);
    Notification::fake();

    $this->post(route('login.store'), [
        'email' => $owner->email,
        'password' => 'password',
    ])->assertRedirect(route('home'));

    $this->get(route('admin'))->assertOk();
});
