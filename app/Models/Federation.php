<?php

namespace App\Models;

use Database\Factories\FederationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A fictional wrestling promotion run by one player.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'description'])]
class Federation extends Model
{
    /** @use HasFactory<FederationFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Wrestler, $this>
     */
    public function wrestlers(): HasMany
    {
        return $this->hasMany(Wrestler::class);
    }

    /**
     * @return HasMany<Title, $this>
     */
    public function titles(): HasMany
    {
        return $this->hasMany(Title::class);
    }

    /**
     * @return HasMany<Rivalry, $this>
     */
    public function rivalries(): HasMany
    {
        return $this->hasMany(Rivalry::class);
    }

    /**
     * @return HasMany<MainEvent, $this>
     */
    public function mainEvents(): HasMany
    {
        return $this->hasMany(MainEvent::class);
    }
}
