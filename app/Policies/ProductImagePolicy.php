<?php

namespace App\Policies;

use App\Models\ProductImage;
use App\Models\User;

class ProductImagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('product-images.view');
    }

    public function view(User $user, ProductImage $productImage): bool
    {
        return $user->can('product-images.view');
    }

    public function create(User $user): bool
    {
        return $user->can('product-images.create');
    }

    public function update(User $user, ProductImage $productImage): bool
    {
        return $user->can('product-images.update');
    }

    public function delete(User $user, ProductImage $productImage): bool
    {
        return $user->can('product-images.delete');
    }

    public function restore(User $user, ProductImage $productImage): bool
    {
        return false;
    }

    public function forceDelete(User $user, ProductImage $productImage): bool
    {
        return false;
    }
}
