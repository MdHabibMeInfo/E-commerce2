<?php

use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->actingAs(completeManagementTwoFactorForTest(createSystemOwnerUser()));
});

it('loads the admin category management index', function () {
    $response = $this->get(route('admin.categories.index'));

    $response->assertOk();
    $response->assertSee('Category Management');
    $response->assertSee('Create Category');
    $response->assertSee('data-admin-search', false);
    $response->assertSee('name="parent_id"', false);
    $response->assertSee('data-admin-filter-input', false);
    $response->assertSee('data-filter-root-option', false);
    $response->assertSee('data-filter-options="status"', false);
    $response->assertDontSee('Sort</th>', false);
    $response->assertDontSee('Sort</th>', false);
});

it('suggests only categories that have children in the parent filter', function () {
    $parentCategory = Category::create([
        'name' => 'Parent Category',
        'slug' => 'parent-category',
        'is_active' => true,
    ]);
    Category::create([
        'parent_id' => $parentCategory->id,
        'name' => 'Child Category',
        'slug' => 'child-category',
        'is_active' => true,
    ]);
    $leafCategory = Category::create([
        'name' => 'Leaf Category',
        'slug' => 'leaf-category',
        'is_active' => true,
    ]);

    $suggestionsUrl = route('admin.categories.suggestions', ['parents_only' => true]);

    $this->get(route('admin.categories.index'))
        ->assertSee('data-filter-suggestions-url="'.$suggestionsUrl.'"', false);

    $this->getJson($suggestionsUrl)
        ->assertOk()
        ->assertJsonFragment(['id' => $parentCategory->id, 'label' => 'Parent Category'])
        ->assertJsonMissing(['id' => $leafCategory->id]);
});

it('paginates categories so all records remain accessible', function () {
    foreach (range(1, 26) as $recordNumber) {
        $category = Category::create([
            'name' => sprintf('Pagination Category %03d', $recordNumber),
            'slug' => sprintf('pagination-category-%03d', $recordNumber),
            'is_active' => true,
            'sort_order' => $recordNumber,
        ]);

        $category->forceFill(['created_at' => now()->addSeconds($recordNumber)])->save();
    }

    $this->get(route('admin.categories.index'))
        ->assertOk()
        ->assertSee('Pagination Category 026')
        ->assertDontSee('Pagination Category 001');

    $this->get(route('admin.categories.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Pagination Category 001')
        ->assertDontSee('Pagination Category 026');
});

it('updates category active status in both directions', function () {
    $category = Category::create([
        'name' => 'Status Category',
        'slug' => 'status-category',
        'is_active' => true,
    ]);

    $this->get(route('admin.categories.edit', $category))
        ->assertOk()
        ->assertSee('type="hidden" name="is_active" value="0"', false);

    $this->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'slug' => $category->slug,
        'is_active' => false,
    ])->assertRedirect(route('admin.categories.index'));

    expect($category->fresh()->is_active)->toBeFalse();
    $this->get(route('admin.categories.index'))
        ->assertOk()
        ->assertSee('Inactive');

    $this->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'slug' => $category->slug,
        'is_active' => true,
    ])->assertRedirect(route('admin.categories.index'));

    expect($category->fresh()->is_active)->toBeTrue();
});

it('finds a category beyond 5000 records and suggests it by slug', function () {
    $parentCategory = Category::create([
        'name' => 'Rare Parent Picker',
        'slug' => 'rare-parent-picker',
        'is_active' => true,
    ]);

    foreach (array_chunk(range(1, 5001), 100) as $recordNumbers) {
        $categories = [];

        foreach ($recordNumbers as $recordNumber) {
            $isSearchTarget = $recordNumber === 5001;
            $categories[] = [
                'parent_id' => $isSearchTarget ? $parentCategory->id : null,
                'name' => $isSearchTarget ? 'Rare Search Category' : sprintf('Bulk Category %04d', $recordNumber),
                'slug' => $isSearchTarget ? 'rare-deep-category-target' : sprintf('bulk-category-%04d', $recordNumber),
                'is_active' => ! $isSearchTarget,
                'sort_order' => $recordNumber,
                'created_at' => now()->subSeconds($recordNumber),
                'updated_at' => now()->subSeconds($recordNumber),
            ];
        }

        DB::table('categories')->insert($categories);
    }

    $this->get(route('admin.categories.index', [
        'search' => 'rare-deep-category-target',
        'status' => 'inactive',
        'parent_id' => $parentCategory->id,
    ]))
        ->assertOk()
        ->assertSee('Rare Search Category')
        ->assertSee('value="'.$parentCategory->id.'"', false);

    $this->getJson(route('admin.categories.suggestions', ['search' => 'rare-deep-category-target']))
        ->assertOk()
        ->assertJsonFragment([
            'id' => Category::query()->where('slug', 'rare-deep-category-target')->value('id'),
            'label' => 'Rare Search Category',
        ]);

    $this->getJson(route('admin.categories.suggestions', ['search' => 'Rare Parent Picker']))
        ->assertOk()
        ->assertJsonFragment(['id' => $parentCategory->id, 'label' => 'Rare Parent Picker']);

    $this->getJson(route('admin.categories.suggestions'))
        ->assertOk()
        ->assertJsonCount(8);

    $deepParentCategory = Category::query()->where('slug', 'rare-deep-category-target')->firstOrFail();
    Category::create([
        'parent_id' => $deepParentCategory->id,
        'name' => 'Child of Deep Parent',
        'slug' => 'child-of-deep-parent',
        'is_active' => true,
    ]);

    $this->get(route('admin.categories.index', ['parent_id' => $deepParentCategory->id]))
        ->assertOk()
        ->assertSee('Child of Deep Parent')
        ->assertSee('value="'.$deepParentCategory->id.'"', false);
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

it('uploads, replaces, and removes category images', function () {
    Storage::fake('public');

    $this->post(route('admin.categories.store'), [
        'name' => 'Image Category',
        'description' => 'Category with an uploaded image.',
        'image' => UploadedFile::fake()->image('first.jpg'),
    ])->assertRedirect(route('admin.categories.index'));

    $category = Category::query()->where('name', 'Image Category')->firstOrFail();
    $firstImagePath = $category->image;

    expect($firstImagePath)->toStartWith('categories/');
    Storage::disk('public')->assertExists($firstImagePath);
    expect($category->image_url)->toBe(Storage::disk('public')->url($firstImagePath));

    $this->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'description' => $category->description,
        'image' => null,
    ])->assertRedirect(route('admin.categories.index'));

    expect($category->fresh()->image)->toBe($firstImagePath);

    $this->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'description' => $category->description,
        'image' => UploadedFile::fake()->image('second.jpg'),
    ])->assertRedirect(route('admin.categories.index'));

    $secondImagePath = $category->fresh()->image;

    expect($secondImagePath)->not->toBe($firstImagePath);
    Storage::disk('public')->assertExists($secondImagePath);
    Storage::disk('public')->assertMissing($firstImagePath);

    $this->delete(route('admin.categories.destroy', $category))
        ->assertRedirect(route('admin.categories.index'));
    Storage::disk('public')->assertExists($secondImagePath);

    $this->post(route('admin.categories.restore', $category))
        ->assertRedirect(route('admin.categories.index'));

    $this->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'description' => $category->description,
        'remove_image' => '1',
    ])->assertRedirect(route('admin.categories.index'));

    expect($category->fresh()->image)->toBeNull();
    Storage::disk('public')->assertMissing($secondImagePath);
});

it('rejects non-image category uploads without persisting a category or file', function () {
    Storage::fake('public');

    $this->from(route('admin.categories.create'))
        ->post(route('admin.categories.store'), [
            'name' => 'Invalid Image Category',
            'image' => UploadedFile::fake()->create('document.pdf', 20, 'application/pdf'),
        ])
        ->assertRedirect(route('admin.categories.create'))
        ->assertSessionHasErrors('image');

    expect(Category::query()->where('name', 'Invalid Image Category')->exists())->toBeFalse();
    expect(Storage::disk('public')->allFiles('categories'))->toBeEmpty();
});

it('filters categories by name and active status', function () {
    Category::create([
        'name' => 'Searchable Active Category',
        'slug' => 'searchable-active-category',
        'is_active' => true,
    ]);
    Category::create([
        'name' => 'Searchable Inactive Category',
        'slug' => 'searchable-inactive-category',
        'is_active' => false,
    ]);

    $this->get(route('admin.categories.index', [
        'search' => 'Searchable',
        'status' => 'active',
    ]))
        ->assertOk()
        ->assertSee('Searchable Active Category')
        ->assertDontSee('Searchable Inactive Category');
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
        ->assertSee('Deleted Categories')
        ->assertSee('data-admin-search', false)
        ->assertSee('data-admin-filter-input', false);
});

it('suggests only soft-deleted categories on the deleted page', function () {
    $deletedCategory = Category::create([
        'name' => 'Soft Deleted Category',
        'slug' => 'soft-deleted-category',
        'is_active' => true,
    ]);
    $deletedCategory->delete();

    $activeCategory = Category::create([
        'name' => 'Active Category Match',
        'slug' => 'active-category-match',
        'is_active' => true,
    ]);

    $this->getJson(route('admin.categories.suggestions', ['search' => 'category', 'trashed' => true]))
        ->assertOk()
        ->assertJsonFragment(['label' => 'Soft Deleted Category'])
        ->assertJsonMissing(['label' => 'Active Category Match']);
});
