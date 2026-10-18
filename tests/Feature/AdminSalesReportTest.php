<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

it('reports paid sales and customer feedback for the selected variant and period', function () {
  $this->travelTo(now()->setDate(2026, 10, 18)->setTime(12, 0));
  $this->actingAs(completeManagementTwoFactorForTest(createSystemOwnerUser()));

  $category = Category::create([
    'name' => 'Autumn Collection',
    'slug' => 'autumn-collection',
    'is_active' => true,
  ]);
  $product = Product::create([
    'category_id' => $category->id,
    'name' => 'Wool Jacket',
    'slug' => 'wool-jacket',
    'type' => 'variable',
    'price' => 100,
    'is_active' => true,
  ]);
  $variant = ProductVariant::create([
    'product_id' => $product->id,
    'name' => 'Forest Green / Medium',
    'sku' => 'JACKET-GREEN-M',
    'combination_hash' => str_repeat('a', 64),
    'price' => 100,
    'stock' => 5,
    'is_active' => true,
  ]);

  $paidOrder = Order::create([
    'order_number' => 'ORD-REPORT-PAID',
    'customer_name' => 'Report Customer',
    'phone' => '01700000000',
    'address' => 'Report address',
    'city' => 'Dhaka',
    'subtotal' => 200,
    'total' => 220,
    'payment_status' => 'paid',
    'payment_reviewed_at' => '2026-10-15 14:00:00',
  ]);
  $paidOrder->items()->create([
    'product_id' => $product->id,
    'product_variant_id' => $variant->id,
    'variant_name' => 'Forest Green / Medium',
    'variant_sku' => 'JACKET-GREEN-M',
    'name' => 'Wool Jacket',
    'price' => 100,
    'quantity' => 2,
  ]);

  $unpaidOrder = Order::create([
    'order_number' => 'ORD-REPORT-UNPAID',
    'customer_name' => 'Unpaid Customer',
    'phone' => '01700000001',
    'address' => 'Report address',
    'city' => 'Dhaka',
    'subtotal' => 900,
    'total' => 900,
    'payment_status' => 'pending',
  ]);
  $unpaidOrder->items()->create([
    'product_id' => $product->id,
    'name' => 'Wool Jacket',
    'price' => 900,
    'quantity' => 1,
  ]);

  $review = $product->reviews()->create([
    'name' => 'Report Reviewer',
    'email' => 'reviewer@example.com',
    'rating' => 2,
    'body' => 'The sizing runs smaller than expected.',
    'images' => [],
  ]);
  $review->forceFill(['created_at' => '2026-10-16 09:00:00'])->save();

  $this->get(route('admin.reports.sales', [
    'start_date' => '2026-10-14',
    'end_date' => '2026-10-16',
    'category_id' => $category->id,
    'product_id' => $product->id,
    'variant_id' => $variant->id,
  ]))
    ->assertOk()
    ->assertSee('Gross merchandise sales')
    ->assertSee('৳200.00')
    ->assertSee('Forest Green / Medium')
    ->assertSee('JACKET-GREEN-M')
    ->assertSee('Report Reviewer')
    ->assertSee('reviewer@example.com')
    ->assertSee('The sizing runs smaller than expected.')
    ->assertSee('data-admin-filter-input', false)
    ->assertSee(route('admin.reports.sales.products'), false)
    ->assertDontSee('name="product_search"', false)
    ->assertDontSee('<select name="product_id"', false)
    ->assertDontSee('ORD-REPORT-UNPAID');
});

it('returns a bounded initial list and indexed-prefix product suggestions', function () {
  $this->actingAs(completeManagementTwoFactorForTest(createSystemOwnerUser()));
  $matchingProduct = Product::create([
    'name' => 'Nokia B-22 Phone',
    'slug' => 'nokia-b-22-phone',
    'sku' => 'PHONE-8842',
    'type' => 'simple',
    'is_active' => true,
  ]);
  Product::create([
    'name' => 'Different Product',
    'slug' => 'nokia-accessory',
    'sku' => 'NOKIA-ACCESSORY',
    'type' => 'simple',
    'is_active' => true,
  ]);
  foreach (range(1, 25) as $number) {
    Product::create([
      'name' => 'Initial Product ' . $number,
      'slug' => 'initial-product-' . $number,
      'type' => 'simple',
      'is_active' => true,
    ]);
  }

  $this->getJson(route('admin.reports.sales.products'))
    ->assertOk()
    ->assertJsonCount(10);

  $this->getJson(route('admin.reports.sales.products', ['search' => 'Initial Product']))
    ->assertOk()
    ->assertJsonCount(20);

  $this->getJson(route('admin.reports.sales.products', ['search' => 'Nokia B']))
    ->assertOk()
    ->assertExactJson([[
      'id' => $matchingProduct->id,
      'label' => 'Nokia B-22 Phone',
      'detail' => 'PHONE-8842',
    ]]);

  $this->getJson(route('admin.reports.sales.products', ['search' => 'NOKIA-ACCESSORY']))
    ->assertOk()
    ->assertJsonFragment(['label' => 'Different Product', 'detail' => 'NOKIA-ACCESSORY']);
});

it('rejects invalid date ranges and variants outside the selected product', function () {
  $admin = completeManagementTwoFactorForTest(createSystemOwnerUser());
  $this->actingAs($admin);
  $product = Product::create([
    'name' => 'Report Filter Product',
    'slug' => 'report-filter-product',
    'type' => 'variable',
    'is_active' => true,
  ]);
  $otherProduct = Product::create([
    'name' => 'Other Report Filter Product',
    'slug' => 'other-report-filter-product',
    'type' => 'variable',
    'is_active' => true,
  ]);
  $variant = ProductVariant::create([
    'product_id' => $otherProduct->id,
    'name' => 'Small',
    'sku' => 'REPORT-FILTER-SMALL',
    'combination_hash' => str_repeat('b', 64),
    'price' => 10,
    'stock' => 5,
    'is_active' => true,
  ]);

  $this->from(route('admin.reports.sales'))
    ->get(route('admin.reports.sales', [
      'start_date' => '2026-10-16',
      'end_date' => '2026-10-14',
    ]))
    ->assertRedirect(route('admin.reports.sales'))
    ->assertSessionHasErrors('end_date');

  $this->from(route('admin.reports.sales'))
    ->get(route('admin.reports.sales', [
      'start_date' => '2026-10-14',
      'end_date' => '2026-10-16',
      'product_id' => $product->id,
      'variant_id' => $variant->id,
    ]))
    ->assertRedirect(route('admin.reports.sales'))
    ->assertSessionHasErrors('variant_id');
});

it('requires an authorized admin to view the sales report', function () {
  $this->get(route('admin.reports.sales'))
    ->assertRedirect(route('login'));

  $this->actingAs(completeManagementTwoFactorForTest(User::factory()->create()))
    ->get(route('admin.reports.sales'))
    ->assertForbidden();
});

it('exports filtered sales as a spreadsheet-safe CSV download', function () {
  $this->travelTo(now()->setDate(2026, 10, 18)->setTime(12, 0));
  $this->actingAs(completeManagementTwoFactorForTest(createSystemOwnerUser()));
  $product = Product::create([
    'name' => '=HYPERLINK("https://example.test")',
    'slug' => 'formula-report-product',
    'type' => 'simple',
    'price' => 25,
    'is_active' => true,
  ]);
  $order = Order::create([
    'order_number' => 'ORD-REPORT-CSV',
    'customer_name' => 'CSV Customer',
    'phone' => '01700000002',
    'address' => 'Report address',
    'city' => 'Dhaka',
    'subtotal' => 25,
    'total' => 25,
    'payment_status' => 'paid',
    'payment_reviewed_at' => '2026-10-18 10:00:00',
  ]);
  $order->items()->create([
    'product_id' => $product->id,
    'name' => '=HYPERLINK("https://example.test")',
    'price' => 25,
    'quantity' => 1,
  ]);

  $response = $this->get(route('admin.reports.sales', [
    'start_date' => '2026-10-18',
    'end_date' => '2026-10-18',
    'export' => 'csv',
  ]));

  $response->assertDownload('sales-report-2026-10-18-to-2026-10-18.csv');
  expect($response->streamedContent())->toContain("'=HYPERLINK");
});
