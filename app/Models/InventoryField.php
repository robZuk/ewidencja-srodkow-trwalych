<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InventoryFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryField extends Model
{
    /** @use HasFactory<InventoryFieldFactory> */
    use HasFactory;

    protected $fillable = ['code', 'name', 'description'];

    /** @return HasMany<Asset, $this> */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function label(): string
    {
        return "{$this->code} — {$this->name}";
    }
}
