<?php

namespace App\Models;

use App\Enums\RivalryStatus;
use Database\Factories\RivalryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A feud between two different wrestlers of the same federation. Intensity goes from 0 to 100.
 * The database checks the two wrestlers differ; that both belong to the federation is up to the code.
 *
 * @property int $id
 * @property int $federation_id
 * @property int $wrestler_a_id
 * @property int $wrestler_b_id
 * @property int $intensity
 * @property RivalryStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['wrestler_a_id', 'wrestler_b_id', 'intensity', 'status'])]
class Rivalry extends Model
{
    /** @use HasFactory<RivalryFactory> */
    use HasFactory;

    /**
     * Same defaults as the database, so a rivalry created in this request already has them.
     *
     * @var array<string, int|string>
     */
    protected $attributes = [
        'intensity' => 50,
        'status' => 'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'intensity' => 'integer',
            'status' => RivalryStatus::class,
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
     * @return BelongsTo<Wrestler, $this>
     */
    public function wrestlerA(): BelongsTo
    {
        return $this->belongsTo(Wrestler::class, 'wrestler_a_id');
    }

    /**
     * @return BelongsTo<Wrestler, $this>
     */
    public function wrestlerB(): BelongsTo
    {
        return $this->belongsTo(Wrestler::class, 'wrestler_b_id');
    }
}
