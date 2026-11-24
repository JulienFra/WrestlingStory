<?php

namespace App\Models;

use App\Enums\Alignment;
use App\Enums\WrestlerStatus;
use Database\Factories\WrestlerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A wrestler of a federation's roster. Each stat goes from 0 to 100 (checked by the database).
 *
 * @property int $id
 * @property int $federation_id
 * @property string $ring_name
 * @property string $gimmick
 * @property Alignment $alignment
 * @property int $power
 * @property int $technique
 * @property int $charisma
 * @property int $stamina
 * @property int $popularity
 * @property WrestlerStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['ring_name', 'gimmick', 'alignment', 'power', 'technique', 'charisma', 'stamina', 'popularity', 'status'])]
class Wrestler extends Model
{
    /** @use HasFactory<WrestlerFactory> */
    use HasFactory;

    /**
     * Same default as the database, so a wrestler created in this request already has it.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'alignment' => Alignment::class,
            'power' => 'integer',
            'technique' => 'integer',
            'charisma' => 'integer',
            'stamina' => 'integer',
            'popularity' => 'integer',
            'status' => WrestlerStatus::class,
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
    public function titleReigns(): HasMany
    {
        return $this->hasMany(TitleReign::class);
    }
}
