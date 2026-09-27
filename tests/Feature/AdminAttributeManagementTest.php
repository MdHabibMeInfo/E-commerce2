<?php

use App\Models\ProductAttribute;

it('loads the admin attribute management index', function () {
  $response = $this->get(route('admin.attributes.index'));

  $response->assertOk();
  $response->assertSee('Attribute Management');
  $response->assertSee('Create Attribute');
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
    ->assertSee('Deleted Attributes');
});
