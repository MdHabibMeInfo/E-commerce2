<?php

use App\Models\Category;

beforeEach(function () {
    $this->actingAs(completeManagementTwoFactorForTest(createSystemOwnerUser()));
});

it('loads the admin category management index', function () {
    $response = $this->get(route('admin.categories.index'));

    $response->assertOk();
    $response->assertSee('Category Management');
    $response->assertSee('Create Category');
});

it('creates, updates, deletes, and restores a category from the admin module', function () {
    $response = $this->post(route('admin.categories.store'), [
        'name' => 'Electronics',
        'slug' => 'electronics',
        'description' => 'Modern electronic devices.',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.categories.index'));
    $this->assertDatabaseHas('categories', ['slug' => 'electronics']);

    $category = Category::query()->where('slug', 'electronics')->firstOrFail();

    $updateResponse = $this->put(route('admin.categories.update', $category), [
        'name' => 'Electronics & Accessories',
        'slug' => 'electronics-accessories',
        'description' => 'Electronic devices and accessories.',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    $updateResponse->assertRedirect(route('admin.categories.index'));
    $this->assertDatabaseHas('categories', ['slug' => 'electronics-accessories']);

    $deleteResponse = $this->delete(route('admin.categories.destroy', $category));
    $deleteResponse->assertRedirect(route('admin.categories.index'));
    $this->assertSoftDeleted('categories', ['id' => $category->id]);

    $restoreResponse = $this->post(route('admin.categories.restore', $category));
    $restoreResponse->assertRedirect(route('admin.categories.index'));
    $this->assertDatabaseHas('categories', ['id' => $category->id, 'deleted_at' => null]);
});

it('loads the category create, edit, and deleted pages in the admin dashboard', function () {
    $category = Category::create([
        'name' => 'Home Goods',
        'slug' => 'home-goods',
        'description' => 'Home and lifestyle essentials.',
        'sort_order' => 5,
        'is_active' => true,
    ]);

    $this->get(route('admin.categories.create'))
        ->assertOk()
        ->assertSee('Create Category');

    $this->get(route('admin.categories.edit', $category))
        ->assertOk()
        ->assertSee('Update Category');

    $this->get(route('admin.categories.trashed'))
        ->assertOk()
        ->assertSee('Deleted Categories');
});
