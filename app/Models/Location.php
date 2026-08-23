<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = ['name'];

    public function activityLabel(): string
    {
        return $this->name;
    }

    /** @return HasMany<Asset, $this> */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
