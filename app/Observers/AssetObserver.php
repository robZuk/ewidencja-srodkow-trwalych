<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Asset;
use App\Models\AssetActivity;
use Illuminate\Support\Facades\Auth;

/**
 * Writes the asset audit trail. Replaces the audit logic that used to live in
 * the legacy Zasoby model's boot() method — same behaviour, proper separation.
 */
class AssetObserver
{
    /** Attributes that should never generate an audit entry on update. */
    private const IGNORED = ['updated_at', 'created_at'];

    public function created(Asset $asset): void
    {
        $this->record($asset, 'created');
    }

    public function updated(Asset $asset): void
    {
        foreach ($asset->getChanges() as $field => $newValue) {
            if (in_array($field, self::IGNORED, true)) {
                continue;
            }

            $this->record(
                $asset,
                'updated',
                $field,
                $asset->getOriginal($field),
                $newValue,
            );
        }
    }

    public function deleted(Asset $asset): void
    {
        $this->record($asset, 'deleted');
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
            'old_value' => $oldValue === null ? null : (string) $oldValue,
            'new_value' => $newValue === null ? null : (string) $newValue,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
        ]);
    }
}
