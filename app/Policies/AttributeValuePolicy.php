<?php

namespace App\Policies;

use App\Models\AttributeValue;
use App\Models\User;

class AttributeValuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attribute-values.view');
    }

    public function view(
        User $user,
        AttributeValue $attributeValue
    ): bool {
        return $user->can('attribute-values.view');
    }

    public function create(User $user): bool
    {
        return $user->can('attribute-values.create');
    }

    public function update(
        User $user,
        AttributeValue $attributeValue
    ): bool {
        return $user->can('attribute-values.update');
    }

    public function delete(
        User $user,
        AttributeValue $attributeValue
    ): bool {
        return $user->can('attribute-values.delete');
    }

    public function restore(
        User $user,
        AttributeValue $attributeValue
    ): bool {
        return $user->can('attribute-values.update');
    }

    public function forceDelete(
        User $user,
        AttributeValue $attributeValue
    ): bool {
        return false;
    }
}
