<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Tryout;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Encapsulates the anti-cheat rules for a tryout attempt: starting an
 * attempt (window check + one-shot random draw), grading on submit, and
 * closing out an attempt whose time has run out. Every time computation
 * here is based on database timestamps only, never on client input.
 */
class AttemptService
{
    /**
     * Start a new attempt for the given user on the given tryout.
     *
     * Validates the tryout is published and within its start/end window,
     * and that the user does not already have an attempt. Draws a random
     * subset of question_count questions from the tryout's question pool,
     * puts them in random display order, and shuffles each question's
     * option order. All of this is persisted once, here, and never
     * recomputed afterwards.
     *
     * @throws RuntimeException when the tryout cannot be started right now
     */
    public function start(Tryout $tryout, User $user): Attempt
    {
        return DB::transaction(function () use ($tryout, $user) {
            $tryout = Tryout::where('id', $tryout->id)->lockForUpdate()->firstOrFail();

            if (! $tryout->isOpenForStart()) {
                throw new RuntimeException('Tryout ini tidak sedang dibuka untuk dimulai.');
            }

            $existing = Attempt::where('tryout_id', $tryout->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                throw new RuntimeException('Anda sudah pernah mengikuti tryout ini.');
            }

            $poolQuestionIds = $tryout->questionPool()->pluck('questions.id')->all();

            if (count($poolQuestionIds) === 0) {
                throw new RuntimeException('Tryout ini belum memiliki soal.');
            }

            shuffle($poolQuestionIds);
            $selectedQuestionIds = array_slice($poolQuestionIds, 0, $tryout->question_count);

            $attempt = Attempt::create([
                'tryout_id' => $tryout->id,
                'user_id' => $user->id,
                'started_at' => now(),
                'status' => 'ongoing',
            ]);

            $questions = $tryout->questionPool()
                ->whereIn('questions.id', $selectedQuestionIds)
                ->with('options')
                ->get()
                ->keyBy('id');

            $order = 1;
            foreach ($selectedQuestionIds as $questionId) {
                $question = $questions->get($questionId);

                if (! $question) {
                    continue;
                }

                $optionIds = $question->options->pluck('id')->all();
                shuffle($optionIds);

                $attempt->attemptQuestions()->create([
                    'question_id' => $question->id,
                    'display_order' => $order,
                    'shuffled_option_order' => $optionIds,
                ]);

                $order++;
            }

            return $attempt->refresh();
        });
    }

    /**
     * Grade and finalize an attempt. $status is either "submitted" (the
     * participant pressed the submit button themselves) or "expired" (the
     * system closed it out because time ran out). Either way the attempt
     * is graded and submitted_at is recorded.
     */
    public function finalize(Attempt $attempt, string $status = 'submitted', ?Carbon $submittedAt = null): Attempt
    {
        return DB::transaction(function () use ($attempt, $status, $submittedAt) {
            $attempt = Attempt::where('id', $attempt->id)->lockForUpdate()->firstOrFail();

            if (! $attempt->isOngoing()) {
                return $attempt;
            }

            $attemptQuestions = $attempt->attemptQuestions()->with('question.options')->get();

            $earnedPoints = 0;
            $totalPoints = 0;

            foreach ($attemptQuestions as $attemptQuestion) {
                $question = $attemptQuestion->question;
                $totalPoints += $question->points;

                $correctOption = $question->options->firstWhere('is_correct', true);
                $isCorrect = $correctOption
                    && $attemptQuestion->selected_option_id
                    && (int) $attemptQuestion->selected_option_id === (int) $correctOption->id;

                $attemptQuestion->is_correct = $isCorrect;
                $attemptQuestion->save();

                if ($isCorrect) {
                    $earnedPoints += $question->points;
                }
            }

            $score = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0.0;

            $attempt->status = $status;
            $attempt->score = $score;
            $attempt->submitted_at = $submittedAt ?? now();
            $attempt->save();

            return $attempt;
        });
    }

    /**
     * Defensive check run on every exam page load and every autosave
     * request: if time is already up for an ongoing attempt, force-submit
     * it right now (status "expired") instead of letting the participant
     * keep answering. Returns the (possibly just-finalized) attempt.
     */
    public function forceSubmitIfExpired(Attempt $attempt): Attempt
    {
        if ($attempt->isOngoing() && $attempt->isTimeUp()) {
            return $this->finalize($attempt, 'expired', $attempt->deadline());
        }

        return $attempt;
    }

    /**
     * Autosave a participant's answer. Refuses silently-wrong writes: if
     * the attempt is no longer ongoing (submitted/expired) the answer is
     * not saved, so a client that raced past the timeout can never sneak
     * in a late answer.
     *
     * @throws RuntimeException when the attempt is no longer ongoing, or
     *                           the option does not belong to this question
     */
    public function answer(Attempt $attempt, int $attemptQuestionId, int $optionId): void
    {
        if (! $attempt->isOngoing()) {
            throw new RuntimeException('Waktu pengerjaan sudah berakhir, jawaban tidak bisa diubah lagi.');
        }

        $attemptQuestion = $attempt->attemptQuestions()->findOrFail($attemptQuestionId);

        if (! in_array($optionId, $attemptQuestion->shuffled_option_order, true)) {
            throw new RuntimeException('Pilihan jawaban tidak valid untuk soal ini.');
        }

        $attemptQuestion->selected_option_id = $optionId;
        $attemptQuestion->answered_at = now();
        $attemptQuestion->save();
    }

    public function findAttemptOrFail(Tryout $tryout, User $user): Attempt
    {
        $attempt = Attempt::where('tryout_id', $tryout->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $attempt) {
            throw new ModelNotFoundException('Attempt tidak ditemukan.');
        }

        return $attempt;
    }
}
