<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Attempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'tryout_id',
        'user_id',
        'started_at',
        'submitted_at',
        'status',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tryout, $this>
     */
    public function tryout(): BelongsTo
    {
        return $this->belongsTo(Tryout::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<AttemptQuestion, $this>
     */
    public function attemptQuestions(): HasMany
    {
        return $this->hasMany(AttemptQuestion::class)->orderBy('display_order');
    }

    /**
     * The server-computed moment this attempt's time is up. Never trust a
     * client-sent value: this is always derived from started_at (DB) plus
     * the tryout's duration_minutes (DB).
     */
    public function deadline(): Carbon
    {
        return $this->started_at->copy()->addMinutes($this->tryout->duration_minutes);
    }

    /**
     * Seconds remaining right now, computed fresh from the database columns.
     * Never negative.
     */
    public function remainingSeconds(?Carbon $now = null): int
    {
        $now ??= now();

        return max(0, $this->deadline()->getTimestamp() - $now->getTimestamp());
    }

    public function isTimeUp(?Carbon $now = null): bool
    {
        return $this->remainingSeconds($now) <= 0;
    }

    public function isOngoing(): bool
    {
        return $this->status === 'ongoing';
    }
}
