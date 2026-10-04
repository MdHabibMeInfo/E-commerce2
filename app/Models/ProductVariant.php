<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'combination_hash',
        'price',
        'compare_at_price',
        'is_out_of_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'is_out_of_stock' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'product_variant_attribute_values'
        );
    }

    public function displayName(): string
    {
        if (filled($this->name)) {
            return $this->name;
        }

        $attributeNames = $this->attributeValues
            ->map(fn (AttributeValue $value): string => trim(
                ($value->productAttribute?->name ?? '').': '.$value->value
            ))
            ->filter(fn (string $name): bool => ! str_starts_with($name, ':'));

        return $attributeNames->isNotEmpty()
            ? $attributeNames->implode(' / ')
            : 'Variant '.$this->id;
    }
}
