<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Tryout extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subject_id',
        'duration_minutes',
        'starts_at',
        'ends_at',
        'question_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return BelongsToMany<Question, $this>
     */
    public function questionPool(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'tryout_questions');
    }

    /**
     * @return HasMany<Attempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function isOpenForStart(?Carbon $now = null): bool
    {
        $now ??= now();

        return $this->status === 'published'
            && $now->greaterThanOrEqualTo($this->starts_at)
            && $now->lessThanOrEqualTo($this->ends_at);
    }

    public function hasSchedulePassed(?Carbon $now = null): bool
    {
        $now ??= now();

        return $now->greaterThan($this->ends_at);
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
