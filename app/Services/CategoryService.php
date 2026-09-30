<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CategoryService
{
    public function create(array $data): Category
    {
        $parentId = $data['parent_id'] ?? null;

        if ($parentId !== null) {
            $this->validateParent($parentId);
        }

        $slug = $this->generateUniqueSlug(
            $data['slug'] ?? null,
            $data['name']
        );
        $imagePath = $this->storeImage($data['image'] ?? null);

        try {
            return DB::transaction(function () use (
                $data,
                $parentId,
                $slug,
                $imagePath
            ) {
                return Category::create([
                    'parent_id' => $parentId,
                    'name' => $data['name'],
                    'slug' => $slug,
                    'description' => $data['description'] ?? null,
                    'image' => $imagePath ?? ($data['image'] ?? null),
                    'is_active' => $data['is_active'] ?? true,
                    'sort_order' => $data['sort_order'] ?? 0,
                ]);
            });
        } catch (Throwable $exception) {
            $this->deleteStoredImage($imagePath);

            throw $exception;
        }
    }

    public function update(
        Category $category,
        array $data
    ): Category {
        $parentId = $data['parent_id'] ?? null;

        if ($parentId !== null) {
            $this->validateParentForUpdate(
                $category,
                $parentId
            );
        }

        $slug = $this->generateUniqueSlug(
            $data['slug'] ?? null,
            $data['name'],
            $category
        );
        $oldImagePath = $category->image;
        $uploadedImagePath = $this->storeImage($data['image'] ?? null);
        $imagePath = $uploadedImagePath
            ?? (array_key_exists('image', $data) && $data['image'] !== null
                ? $data['image']
                : $oldImagePath);

        if (($data['remove_image'] ?? false) && $uploadedImagePath === null) {
            $imagePath = null;
        }

        try {
            $updatedCategory = DB::transaction(function () use (
                $category,
                $data,
                $parentId,
                $slug,
                $imagePath
            ) {
                $category->update([
                    'parent_id' => $parentId,
                    'name' => $data['name'],
                    'slug' => $slug,
                    'description' => $data['description'] ?? null,
                    'image' => $imagePath,
                    'is_active' => $data['is_active'] ?? $category->is_active,
                    'sort_order' => $data['sort_order'] ?? $category->sort_order,
                ]);

                return $category->refresh();
            });
        } catch (Throwable $exception) {
            $this->deleteStoredImage($uploadedImagePath);

            throw $exception;
        }

        if ($oldImagePath !== $imagePath) {
            $this->deleteStoredImage($oldImagePath);
        }

        return $updatedCategory;
    }

    public function delete(Category $category): void
    {
        if ($category->children()->exists()) {
            throw ValidationException::withMessages([
                'category' => [
                    'This category cannot be deleted because it has child categories.',
                ],
            ]);
        }

        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => [
                    'This category cannot be deleted because it has products.',
                ],
            ]);
        }

        $category->delete();
    }

    public function restore(Category $category): Category
    {
        if (! $category->trashed()) {
            throw ValidationException::withMessages([
                'category' => [
                    'This category is not deleted.',
                ],
            ]);
        }

        if ($category->parent_id !== null) {
            $parent = Category::query()
                ->whereKey($category->parent_id)
                ->whereNull('deleted_at')
                ->first();

            if (! $parent) {
                throw ValidationException::withMessages([
                    'parent_id' => [
                        'The parent category is deleted or no longer exists.',
                    ],
                ]);
            }
        }

        return DB::transaction(function () use ($category) {
            $category->restore();

            return $category->refresh();
        });
    }

    private function validateParent(int $parentId): void
    {
        $parent = Category::query()
            ->whereKey($parentId)
            ->whereNull('deleted_at')
            ->first();

        if (! $parent) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    'The selected parent category is invalid or deleted.',
                ],
            ]);
        }
    }

    private function validateParentForUpdate(
        Category $category,
        int $parentId
    ): void {
        if ($category->id === $parentId) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    'A category cannot be its own parent.',
                ],
            ]);
        }

        $this->validateParent($parentId);

        $descendantIds = $this->getDescendantIds($category);

        if (in_array($parentId, $descendantIds, true)) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    'A category cannot have one of its descendants as its parent.',
                ],
            ]);
        }
    }

    private function getDescendantIds(Category $category): array
    {
        $descendantIds = [];

        $children = $category->children()->get();

        foreach ($children as $child) {
            $descendantIds[] = $child->id;

            $descendantIds = array_merge(
                $descendantIds,
                $this->getDescendantIds($child)
            );
        }

        return $descendantIds;
    }

    private function storeImage(mixed $image): ?string
    {
        if (! $image instanceof UploadedFile) {
            return null;
        }

        $path = $image->store('categories', 'public');

        if (! is_string($path)) {
            throw new \RuntimeException('The category image could not be stored.');
        }

        return $path;
    }

    private function deleteStoredImage(?string $imagePath): void
    {
        if ($imagePath !== null && Str::startsWith($imagePath, 'categories/')) {
            Storage::disk('public')->delete($imagePath);
        }
    }

    private function generateUniqueSlug(
        ?string $requestedSlug,
        string $name,
        ?Category $ignore = null
    ): string {
        $baseSlug = Str::slug(
            trim((string) ($requestedSlug ?? '')) ?: $name
        );

        $slug = $baseSlug;
        $counter = 2;

        while (
            Category::withTrashed()
                ->where('slug', $slug)
                ->when(
                    $ignore,
                    fn ($query) => $query->where('id', '!=', $ignore->id)
                )
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
