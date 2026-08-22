<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use Database\Factories\TransferRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransferRequest extends Model
{
    /** @use HasFactory<TransferRequestFactory> */
    use HasFactory, SoftDeletes;

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
}
