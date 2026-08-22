<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetActivity extends Model
{
    /** Append-only: only a created_at timestamp is tracked. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'asset_id',
        'event',
        'field',
        'old_value',
        'new_value',
        'user_id',
        'user_name',
    ];

    /** @return BelongsTo<Asset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
