<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Courier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_active',
        'configuration',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'configuration' => 'array',
        ];
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(CourierShipment::class);
    }
}
