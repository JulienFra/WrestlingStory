<?php

namespace App\Models;

use App\Enums\MainEventStatus;
use App\Enums\MainEventType;
use Database\Factories\MainEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A main event booked by the player and told by the language model (D-004).
 * The database checks its shape (two different wrestlers, a winner among them, a played match has its story);
 * that everything belongs to the federation, the dates and the title rules are up to the code.
 * follow_up is shown only to the owner, never on the public page.
 *
 * @property int $id
 * @property int $federation_id
 * @property MainEventType $type
 * @property int|null $rivalry_id
 * @property int $wrestler_a_id
 * @property int $wrestler_b_id
 * @property int|null $title_id
 * @property Carbon $scheduled_on
 * @property int|null $imposed_winner_id
 * @property MainEventStatus $status
 * @property int|null $winner_id
 * @property string|null $story
 * @property string|null $follow_up
 * @property Carbon|null $played_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['type', 'rivalry_id', 'wrestler_a_id', 'wrestler_b_id', 'title_id', 'scheduled_on', 'imposed_winner_id'])]
class MainEvent extends Model
{
    /** @use HasFactory<MainEventFactory> */
    use HasFactory;

    /**
     * Same default as the database, so a main event created in this request already has it.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => 'scheduled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MainEventType::class,
            'status' => MainEventStatus::class,
            'scheduled_on' => 'date',
            'played_at' => 'datetime',
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
     * @return BelongsTo<Rivalry, $this>
     */
    public function rivalry(): BelongsTo
    {
        return $this->belongsTo(Rivalry::class);
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

    /**
     * The title at stake, or null.
     *
     * @return BelongsTo<Title, $this>
     */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    /**
     * @return BelongsTo<Wrestler, $this>
     */
    public function imposedWinner(): BelongsTo
    {
        return $this->belongsTo(Wrestler::class, 'imposed_winner_id');
    }

    /**
     * @return BelongsTo<Wrestler, $this>
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(Wrestler::class, 'winner_id');
    }
}
