<?php

namespace App\Models;

use Database\Factories\TitleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A championship of a federation. Prestige goes from 0 to 100 (checked by the database).
 *
 * @property int $id
 * @property int $federation_id
 * @property string $name
 * @property int $prestige
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'prestige'])]
class Title extends Model
{
    /** @use HasFactory<TitleFactory> */
    use HasFactory;

    /**
     * Same default as the database, so a title created in this request already has it.
     *
     * @var array<string, int>
     */
    protected $attributes = [
        'prestige' => 50,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prestige' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Federation, $this>
     */
    public function federation(): BelongsTo
    {
        return $this->belongsTo(Federation::class);
    }

    /**
     * @return HasMany<TitleReign, $this>
     */
    public function reigns(): HasMany
    {
        return $this->hasMany(TitleReign::class);
    }

    /**
     * The reign of the current champion, or null when the title is vacant.
     * The database allows only one open reign per title.
     *
     * @return HasOne<TitleReign, $this>
     */
    public function currentReign(): HasOne
    {
        return $this->hasOne(TitleReign::class)->whereNull('ended_at');
    }
}
