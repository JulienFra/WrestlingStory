<?php

namespace App\Models;

use Database\Factories\AiCallFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One attempt to call the language model, written by App\Ai\AiGateway.
 * error: an App\Ai\AiErrorKind value, "quota" when the daily quota refused the call, or null on success.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $feature
 * @property string $provider
 * @property string $model
 * @property int $input_tokens
 * @property int $output_tokens
 * @property float $cost
 * @property int $duration_ms
 * @property bool $success
 * @property string|null $error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['feature', 'provider', 'model', 'input_tokens', 'output_tokens', 'cost', 'duration_ms', 'success', 'error'])]
class AiCall extends Model
{
    /** @use HasFactory<AiCallFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'cost' => 'float',
            'duration_ms' => 'integer',
            'success' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
