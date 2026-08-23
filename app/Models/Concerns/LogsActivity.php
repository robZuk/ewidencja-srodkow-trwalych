<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Activity;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

/**
 * Records created/updated/deleted events into the system activity log.
 * Models using this trait may override activityLabel() and activityIgnored().
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => $model->recordActivity('created'));
        static::updated(fn ($model) => $model->recordActivity('updated'));
        static::deleted(fn ($model) => $model->recordActivity('deleted'));
    }

    /** @return MorphMany<Activity, $this> */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function recordActivity(string $event): void
    {
        $properties = null;

        if ($event === 'updated') {
            $properties = $this->activityChanges();

            if ($properties === []) {
                return; // nothing meaningful changed
            }
        }

        Activity::create([
            'event' => $event,
            'subject_type' => $this->getMorphClass(),
            'subject_id' => $this->getKey(),
            'subject_label' => $this->activityLabel(),
            'properties' => $properties,
            'causer_id' => Auth::id(),
            'causer_name' => Auth::user()?->name,
        ]);
    }

    /** Human-readable label for this record in the activity log. */
    public function activityLabel(): string
    {
        return class_basename($this).' #'.$this->getKey();
    }

    /**
     * Changed attributes as field => [old, new], excluding noise/sensitive keys.
     *
     * @return array<string, array{old: ?string, new: ?string}>
     */
    protected function activityChanges(): array
    {
        $ignored = array_merge(
            ['created_at', 'updated_at', 'password', 'remember_token'],
            $this->activityIgnored(),
        );

        $changes = [];

        foreach ($this->getChanges() as $key => $new) {
            if (in_array($key, $ignored, true)) {
                continue;
            }

            $changes[$key] = [
                'old' => $this->stringifyActivity($this->getOriginal($key)),
                'new' => $this->stringifyActivity($new),
            ];
        }

        return $changes;
    }

    /** @return list<string> */
    protected function activityIgnored(): array
    {
        return [];
    }

    protected function stringifyActivity(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            is_bool($value) => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
