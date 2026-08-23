<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $event
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $subject_label
 * @property array<string, array{old: ?string, new: ?string}>|null $properties
 * @property int|null $causer_id
 * @property string|null $causer_name
 */
class Activity extends Model
{
    public $table = 'activity_log';

    /** Append-only: only a created_at timestamp is tracked. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'event',
        'subject_type',
        'subject_id',
        'subject_label',
        'properties',
        'causer_id',
        'causer_name',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['properties' => 'array'];
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
}
