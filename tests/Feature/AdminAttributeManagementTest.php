<?php

use App\Models\ProductAttribute;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->actingAs(completeManagementTwoFactorForTest(createSystemOwnerUser()));
});

it('loads the admin attribute management index', function () {
    $response = $this->get(route('admin.attributes.index'));

    $response->assertOk();
    $response->assertSee('Attribute Management');
    $response->assertSee('Create Attribute');
    $response->assertSee('data-admin-search', false);
    $response->assertSee('name="status"', false);
    $response->assertSee('data-filter-options="status"', false);
    $response->assertDontSee('Sort</th>', false);
});

it('manages attribute values from the attribute detail page', function () {
    $attribute = ProductAttribute::create([
        'name' => 'Size',
        'slug' => 'size',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $this->get(route('admin.attributes.show', $attribute))
        ->assertOk()
        ->assertSee('Attribute Values')
        ->assertSee(route('admin.attributes.values.store', $attribute), false);
});

it('completes attribute-value create update delete and restore from the detail page', function () {
    $attribute = ProductAttribute::create([
        'name' => 'Material',
        'slug' => 'material',
        'is_active' => true,
    ]);

    $this->post(route('admin.attributes.values.store', $attribute), [
        'value' => 'Cotton',
        'slug' => 'cotton',
        'sort_order' => 1,
        'is_active' => true,
    ])->assertRedirect(route('admin.attributes.show', $attribute));

    $value = $attribute->values()->firstOrFail();

    $this->put(route('admin.attributes.values.update', [$attribute, $value]), [
        'value' => 'Organic Cotton',
        'slug' => 'organic-cotton',
        'sort_order' => 2,
        'is_active' => true,
    ])->assertRedirect(route('admin.attributes.show', $attribute));

    expect($value->fresh()->slug)->toBe('organic-cotton');

    $this->delete(route('admin.attributes.values.destroy', [$attribute, $value]))
        ->assertRedirect(route('admin.attributes.show', $attribute));
    $this->assertSoftDeleted('attribute_values', ['id' => $value->id]);

    $this->post(route('admin.attributes.values.restore', [$attribute, $value]))
        ->assertRedirect(route('admin.attributes.show', $attribute));
    $this->assertDatabaseHas('attribute_values', ['id' => $value->id, 'deleted_at' => null]);
});

it('paginates attributes so all records remain accessible', function () {
    foreach (range(1, 26) as $recordNumber) {
        $attribute = ProductAttribute::create([
            'name' => sprintf('Pagination Attribute %03d', $recordNumber),
            'slug' => sprintf('pagination-attribute-%03d', $recordNumber),
            'sort_order' => $recordNumber,
            'is_active' => true,
        ]);

        $attribute->forceFill(['created_at' => now()->addSeconds($recordNumber)])->save();
    }

    $this->get(route('admin.attributes.index'))
        ->assertOk()
        ->assertSee('Pagination Attribute 026')
        ->assertDontSee('Pagination Attribute 001');

    $this->get(route('admin.attributes.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Pagination Attribute 001')
        ->assertDontSee('Pagination Attribute 026');
});

it('updates attribute active status in both directions', function () {
    $attribute = ProductAttribute::create([
        'name' => 'Status Attribute',
        'slug' => 'status-attribute',
        'is_active' => true,
    ]);

    $this->get(route('admin.attributes.edit', $attribute))
        ->assertOk()
        ->assertSee('type="hidden" name="is_active" value="0"', false);

    $this->put(route('admin.attributes.update', $attribute), [
        'name' => $attribute->name,
        'slug' => $attribute->slug,
        'is_active' => false,
    ])->assertRedirect(route('admin.attributes.index'));

    expect($attribute->fresh()->is_active)->toBeFalse();
    $this->get(route('admin.attributes.index'))
        ->assertOk()
        ->assertSee('Inactive');

    $this->put(route('admin.attributes.update', $attribute), [
        'name' => $attribute->name,
        'slug' => $attribute->slug,
        'is_active' => true,
    ])->assertRedirect(route('admin.attributes.index'));

    expect($attribute->fresh()->is_active)->toBeTrue();
});

it('finds an attribute beyond 5000 records and suggests it by slug', function () {
    foreach (array_chunk(range(1, 5001), 100) as $recordNumbers) {
        $attributes = [];

        foreach ($recordNumbers as $recordNumber) {
            $isSearchTarget = $recordNumber === 5001;
            $attributes[] = [
                'name' => $isSearchTarget ? 'Rare Search Attribute' : sprintf('Bulk Attribute %04d', $recordNumber),
                'slug' => $isSearchTarget ? 'rare-deep-attribute-target' : sprintf('bulk-attribute-%04d', $recordNumber),
                'sort_order' => $recordNumber,
                'is_active' => ! $isSearchTarget,
                'created_at' => now()->subSeconds($recordNumber),
                'updated_at' => now()->subSeconds($recordNumber),
            ];
        }

        DB::table('product_attributes')->insert($attributes);
    }

    $this->get(route('admin.attributes.index', [
        'search' => 'rare-deep-attribute-target',
        'status' => 'inactive',
    ]))
        ->assertOk()
        ->assertSee('Rare Search Attribute');

    $this->getJson(route('admin.attributes.suggestions', ['search' => 'rare-deep-attribute-target']))
        ->assertOk()
        ->assertJsonFragment(['label' => 'Rare Search Attribute']);

    $this->getJson(route('admin.attributes.suggestions'))
        ->assertOk()
        ->assertJsonCount(8);
});

it('creates, updates, deletes, and restores an attribute from the admin module', function () {
    $response = $this->post(route('admin.attributes.store'), [
        'name' => 'Color',
        'slug' => 'color',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.attributes.index'));
    $this->assertDatabaseHas('product_attributes', ['slug' => 'color']);

    $attribute = ProductAttribute::query()->where('slug', 'color')->firstOrFail();

    $updateResponse = $this->put(route('admin.attributes.update', $attribute), [
        'name' => 'Color Family',
        'slug' => 'color-family',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    $updateResponse->assertRedirect(route('admin.attributes.index'));
    $this->assertDatabaseHas('product_attributes', ['slug' => 'color-family']);

    $deleteResponse = $this->delete(route('admin.attributes.destroy', $attribute));
    $deleteResponse->assertRedirect(route('admin.attributes.index'));
    $this->assertSoftDeleted('product_attributes', ['id' => $attribute->id]);

    $restoreResponse = $this->post(route('admin.attributes.restore', $attribute));
    $restoreResponse->assertRedirect(route('admin.attributes.index'));
    $this->assertDatabaseHas('product_attributes', ['id' => $attribute->id, 'deleted_at' => null]);
});

it('loads the attribute create, edit, and deleted pages in the admin dashboard', function () {
    $attribute = ProductAttribute::create([
        'name' => 'Size',
        'slug' => 'size',
        'sort_order' => 5,
        'is_active' => true,
    ]);

    $this->get(route('admin.attributes.create'))
        ->assertOk()
        ->assertSee('Create Attribute');

    $this->get(route('admin.attributes.edit', $attribute))
        ->assertOk()
        ->assertSee('Update Attribute');

    $this->get(route('admin.attributes.trashed'))
        ->assertOk()
        ->assertSee('Deleted Attributes')
        ->assertSee('data-admin-search', false)
        ->assertSee('data-admin-filter-input', false);
});

it('suggests only soft-deleted attributes on the deleted page', function () {
    $deletedAttribute = ProductAttribute::create([
        'name' => 'Soft Deleted Attribute',
        'slug' => 'soft-deleted-attribute',
        'sort_order' => 1,
        'is_active' => true,
    ]);
    $deletedAttribute->delete();

    $activeAttribute = ProductAttribute::create([
        'name' => 'Active Attribute Match',
        'slug' => 'active-attribute-match',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    $this->getJson(route('admin.attributes.suggestions', ['search' => 'attribute', 'trashed' => true]))
        ->assertOk()
        ->assertJsonFragment(['label' => 'Soft Deleted Attribute'])
        ->assertJsonMissing(['label' => 'Active Attribute Match']);
});
