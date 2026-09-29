<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'display_order',
        'shuffled_option_order',
        'selected_option_id',
        'is_correct',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'shuffled_option_order' => 'array',
            'is_correct' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Attempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * @return BelongsTo<QuestionOption, $this>
     */
    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }

    /**
     * The question's options, in the randomized order shown to this
     * participant, respecting shuffled_option_order.
     *
     * @return \Illuminate\Support\Collection<int, QuestionOption>
     */
    public function orderedOptions(): \Illuminate\Support\Collection
    {
        $options = $this->question->options->keyBy('id');

        return collect($this->shuffled_option_order)
            ->map(fn ($optionId) => $options->get($optionId))
            ->filter();
    }
}
