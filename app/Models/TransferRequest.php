<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Concerns\LogsActivity;
use Database\Factories\TransferRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property TransferType $type
 * @property TransferStatus $status
 * @property int|null $asset_id
 * @property array<string, mixed>|null $asset_snapshot
 * @property int $source_field_id
 * @property int|null $target_field_id
 * @property int|null $requested_by
 * @property int|null $target_accepted_by
 * @property int|null $inventory_accepted_by
 * @property string|null $zmu_number
 * @property string|null $note
 * @property Carbon|null $resolved_at
 * @property-read Asset|null $asset
 * @property-read InventoryField|null $sourceField
 * @property-read InventoryField|null $targetField
 * @property-read User|null $requester
 */
class TransferRequest extends Model
{
    /** @use HasFactory<TransferRequestFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    public function activityLabel(): string
    {
        return 'Zgłoszenie #'.$this->id.' ('.$this->type->label().')';
    }

    protected $fillable = [
        'type',
        'status',
        'asset_id',
        'asset_snapshot',
        'source_field_id',
        'target_field_id',
        'requested_by',
        'target_accepted_by',
        'inventory_accepted_by',
        'zmu_number',
        'note',
        'resolved_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => TransferType::class,
            'status' => TransferStatus::class,
            'asset_snapshot' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    // -- Relations -----------------------------------------------------------

    /** @return BelongsTo<Asset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /** @return BelongsTo<InventoryField, $this> */
    public function sourceField(): BelongsTo
    {
        return $this->belongsTo(InventoryField::class, 'source_field_id');
    }

    /** @return BelongsTo<InventoryField, $this> */
    public function targetField(): BelongsTo
    {
        return $this->belongsTo(InventoryField::class, 'target_field_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    // -- Query scopes --------------------------------------------------------

    /**
     * @param  Builder<TransferRequest>  $query
     * @return Builder<TransferRequest>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            TransferStatus::Completed->value,
            TransferStatus::Rejected->value,
        ]);
    }

    /**
     * @param  Builder<TransferRequest>  $query
     * @return Builder<TransferRequest>
     */
    public function scopeOfType(Builder $query, TransferType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    /**
     * Open requests awaiting a decision the given user is allowed to make
     * (step 1 as a target-field member, step 2 as the inventory section).
     *
     * @param  Builder<TransferRequest>  $query
     * @return Builder<TransferRequest>
     */
    public function scopeActionableBy(Builder $query, User $user): Builder
    {
        return $query->open()->where(function (Builder $query) use ($user): void {
            $query->whereRaw('1 = 0');

            // Step 2 — inventory section confirms PendingInventory requests.
            if ($user->can('decide transfers')) {
                $query->orWhere('status', TransferStatus::PendingInventory->value);
            }

            // Step 1 — target-field members (or admins) accept Pending transfers.
            if ($user->hasRole('admin')) {
                $query->orWhere('status', TransferStatus::Pending->value);
            } elseif ($user->can('request transfers')) {
                $fieldIds = $user->inventoryFields()->pluck('inventory_fields.id')->all();

                if ($fieldIds !== []) {
                    $query->orWhere(fn (Builder $q) => $q
                        ->where('status', TransferStatus::Pending->value)
                        ->whereIn('target_field_id', $fieldIds));
                }
            }
        });
    }
}
