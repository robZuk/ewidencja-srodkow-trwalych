<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssetStatus;
use App\Observers\AssetObserver;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $inventory_number
 * @property string $name
 * @property string|null $description
 * @property string|null $purchase_doc_number
 * @property numeric-string $value
 * @property Carbon|null $purchase_date
 * @property Carbon|null $liquidation_date
 * @property int $quantity
 * @property string|null $asset_type
 * @property int|null $location_id
 * @property int $inventory_field_id
 * @property AssetStatus $status
 * @property string|null $comment
 * @property-read InventoryField|null $inventoryField
 * @property-read Location|null $location
 */
#[ObservedBy(AssetObserver::class)]
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inventory_number',
        'name',
        'description',
        'purchase_doc_number',
        'value',
        'purchase_date',
        'liquidation_date',
        'quantity',
        'asset_type',
        'location_id',
        'inventory_field_id',
        'status',
        'comment',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'purchase_date' => 'date',
            'liquidation_date' => 'date',
            'quantity' => 'integer',
            'status' => AssetStatus::class,
        ];
    }

    /** Per-item value (total value / quantity) used to classify the asset type. */
    public function unitValue(): float
    {
        return (float) $this->value / max(1, $this->quantity);
    }

    // -- Relations -----------------------------------------------------------

    /** @return BelongsTo<InventoryField, $this> */
    public function inventoryField(): BelongsTo
    {
        return $this->belongsTo(InventoryField::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return HasMany<AssetActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(AssetActivity::class)->latest('created_at');
    }

    /** @return HasMany<TransferRequest, $this> */
    public function transferRequests(): HasMany
    {
        return $this->hasMany(TransferRequest::class);
    }

    /**
     * An asset is locked while it has an open (unresolved) transfer or liquidation
     * request — it must not be edited or deleted until the request is settled.
     */
    public function isLockedForEditing(): bool
    {
        return $this->transferRequests()->open()->exists();
    }

    // -- Query scopes --------------------------------------------------------

    /**
     * Full-text-ish search across name, inventory number and description.
     *
     * @param  Builder<Asset>  $query
     * @return Builder<Asset>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('inventory_number', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * @param  Builder<Asset>  $query
     * @return Builder<Asset>
     */
    public function scopeForField(Builder $query, int|string|null $fieldId): Builder
    {
        return blank($fieldId) ? $query : $query->where('inventory_field_id', $fieldId);
    }

    /**
     * @param  Builder<Asset>  $query
     * @return Builder<Asset>
     */
    public function scopeWithStatus(Builder $query, ?string $status): Builder
    {
        return blank($status) ? $query : $query->where('status', $status);
    }

    /**
     * @param  Builder<Asset>  $query
     * @return Builder<Asset>
     */
    public function scopeWithType(Builder $query, ?string $type): Builder
    {
        return blank($type) ? $query : $query->where('asset_type', $type);
    }
}
