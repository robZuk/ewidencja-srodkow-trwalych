<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Activity;
use App\Models\Asset;
use App\Models\AssetActivity;
use Illuminate\Support\Facades\Auth;

/**
 * Writes the asset audit trail: a detailed per-field history in asset_activities
 * (shown on the asset history page) plus a summary entry in the system-wide
 * activity_log (shown to admins on the "Historia zmian" screen).
 */
class AssetObserver
{
    /** Attributes that should never generate an audit entry on update. */
    private const IGNORED = ['updated_at', 'created_at'];

    public function created(Asset $asset): void
    {
        $this->record($asset, 'created');
        $this->logActivity($asset, 'created');
    }

    public function updated(Asset $asset): void
    {
        $changes = [];

        foreach ($asset->getChanges() as $field => $newValue) {
            if (in_array($field, self::IGNORED, true)) {
                continue;
            }

            $old = $asset->getOriginal($field);
            $this->record($asset, 'updated', $field, $old, $newValue);
            $changes[$field] = ['old' => $this->stringify($old), 'new' => $this->stringify($newValue)];
        }

        if ($changes !== []) {
            $this->logActivity($asset, 'updated', $changes);
        }
    }

    public function deleted(Asset $asset): void
    {
        $this->record($asset, 'deleted');
        $this->logActivity($asset, 'deleted');
    }

    /**
     * Summary entry in the system activity log.
     *
     * @param  array<string, array{old: ?string, new: ?string}>|null  $properties
     */
    private function logActivity(Asset $asset, string $event, ?array $properties = null): void
    {
        Activity::create([
            'event' => $event,
            'subject_type' => $asset->getMorphClass(),
            'subject_id' => $asset->getKey(),
            'subject_label' => "{$asset->inventory_number} · {$asset->name}",
            'properties' => $properties,
            'causer_id' => Auth::id(),
            'causer_name' => Auth::user()?->name,
        ]);
    }

    private function record(
        Asset $asset,
        string $event,
        ?string $field = null,
        mixed $oldValue = null,
        mixed $newValue = null,
    ): void {
        AssetActivity::create([
            'asset_id' => $asset->getKey(),
            'event' => $event,
            'field' => $field,
            'old_value' => $this->stringify($oldValue),
            'new_value' => $this->stringify($newValue),
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
        ]);
    }

    /** Convert any attribute value (enum, date, bool, scalar) to a display string. */
    private function stringify(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            $value instanceof \BackedEnum => (string) $value->value,
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
            is_bool($value) => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
