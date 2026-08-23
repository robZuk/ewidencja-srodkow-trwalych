<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\InventoryFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 */
class InventoryField extends Model
{
    /** @use HasFactory<InventoryFieldFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = ['code', 'name', 'description'];

    public function activityLabel(): string
    {
        return $this->label();
    }

    /** @return HasMany<Asset, $this> */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Users who belong to this field (its recipients/operators).
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function label(): string
    {
        return "{$this->code} — {$this->name}";
    }
}
