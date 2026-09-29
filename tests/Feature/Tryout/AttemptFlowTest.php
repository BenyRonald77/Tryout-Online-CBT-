<?php

namespace Tests\Feature\Tryout;

use App\Livewire\Peserta\ExamPage;
use App\Livewire\Peserta\RankingPage;
use App\Models\Attempt;
use App\Models\AttemptQuestion;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Tryout;
use App\Models\User;
use App\Services\AttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttemptFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Creates $count questions (each with 4 options, exactly one correct)
     * attached to the given tryout's pool.
     *
     * @return \Illuminate\Support\Collection<int, Question>
     */
    private function makeQuestionPool(Tryout $tryout, int $count): \Illuminate\Support\Collection
    {
        $subject = Subject::factory()->create();

        $questions = collect(range(1, $count))->map(function (int $i) use ($subject) {
            $question = Question::create([
                'subject_id' => $subject->id,
                'body' => "Soal nomor {$i}",
                'difficulty' => 'easy',
                'points' => 1,
            ]);

            $question->options()->createMany([
                ['body' => 'A', 'is_correct' => true],
                ['body' => 'B', 'is_correct' => false],
                ['body' => 'C', 'is_correct' => false],
                ['body' => 'D', 'is_correct' => false],
            ]);

            return $question;
        });

        $tryout->questionPool()->attach($questions->pluck('id'));

        return $questions;
    }

    private function makeTryout(array $overrides = []): Tryout
    {
        return Tryout::create(array_merge([
            'title' => 'Tryout Uji Coba',
            'subject_id' => null,
            'duration_minutes' => 30,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'question_count' => 5,
            'status' => 'published',
        ], $overrides));
    }

    public function test_starting_attempt_creates_a_randomized_question_set(): void
    {
        $tryout = $this->makeTryout(['question_count' => 5]);
        $this->makeQuestionPool($tryout, 10);
        $user = User::factory()->create(['role' => 'peserta']);

        $attempt = app(AttemptService::class)->start($tryout, $user);

        $this->assertDatabaseHas('attempts', [
            'id' => $attempt->id,
            'tryout_id' => $tryout->id,
            'user_id' => $user->id,
            'status' => 'ongoing',
        ]);

        $attemptQuestions = AttemptQuestion::where('attempt_id', $attempt->id)->orderBy('display_order')->get();

        // Exactly question_count questions were drawn, not the whole pool.
        $this->assertCount(5, $attemptQuestions);

        // display_order is a contiguous 1..N sequence (the randomized order for this participant).
        $this->assertSame([1, 2, 3, 4, 5], $attemptQuestions->pluck('display_order')->all());

        // Each question got its own shuffled option order persisted, containing
        // exactly the 4 option ids that belong to that question.
        foreach ($attemptQuestions as $aq) {
            $optionIds = $aq->question->options->pluck('id')->sort()->values()->all();
            $shuffled = collect($aq->shuffled_option_order)->sort()->values()->all();
            $this->assertSame($optionIds, $shuffled);
        }

        // No duplicate questions drawn.
        $this->assertSame($attemptQuestions->pluck('question_id')->unique()->count(), $attemptQuestions->count());
    }

    public function test_starting_attempt_twice_is_rejected(): void
    {
        $tryout = $this->makeTryout();
        $this->makeQuestionPool($tryout, 10);
        $user = User::factory()->create(['role' => 'peserta']);

        app(AttemptService::class)->start($tryout, $user);

        $this->expectException(\RuntimeException::class);
        app(AttemptService::class)->start($tryout, $user);
    }

    public function test_autosave_persists_an_answer(): void
    {
        $tryout = $this->makeTryout();
        $this->makeQuestionPool($tryout, 10);
        $user = User::factory()->create(['role' => 'peserta']);
        $attempt = app(AttemptService::class)->start($tryout, $user);

        $firstQuestion = AttemptQuestion::where('attempt_id', $attempt->id)->orderBy('display_order')->first();
        $optionToPick = $firstQuestion->shuffled_option_order[0];

        Livewire::actingAs($user)
            ->test(ExamPage::class, ['tryout' => $tryout])
            ->call('selectOption', $firstQuestion->id, $optionToPick)
            ->assertSet('saveState', 'saved');

        $this->assertDatabaseHas('attempt_questions', [
            'id' => $firstQuestion->id,
            'selected_option_id' => $optionToPick,
        ]);

        $firstQuestion->refresh();
        $this->assertNotNull($firstQuestion->answered_at);

        // Navigating away and back must not lose the saved answer.
        $component = Livewire::actingAs($user)->test(ExamPage::class, ['tryout' => $tryout]);
        $component->assertSet('selectedOptionId', $optionToPick);
    }

    public function test_submitting_computes_a_score(): void
    {
        $tryout = $this->makeTryout(['question_count' => 4]);
        $this->makeQuestionPool($tryout, 4);
        $user = User::factory()->create(['role' => 'peserta']);
        $attempt = app(AttemptService::class)->start($tryout, $user);

        $attemptQuestions = AttemptQuestion::where('attempt_id', $attempt->id)->orderBy('display_order')->get();

        // Answer 3 out of 4 correctly (the correct option's body is always "A").
        foreach ($attemptQuestions->take(3) as $aq) {
            $correctOptionId = $aq->question->options()->where('is_correct', true)->first()->id;
            app(AttemptService::class)->answer($attempt, $aq->id, $correctOptionId);
        }

        // Leave the 4th unanswered.

        $service = app(AttemptService::class);
        $finalized = $service->finalize($attempt, 'submitted');

        $this->assertSame('submitted', $finalized->status);
        $this->assertNotNull($finalized->submitted_at);
        $this->assertEqualsWithDelta(75.0, $finalized->score, 0.01);
    }

    public function test_expired_attempt_cannot_be_answered_further(): void
    {
        $tryout = $this->makeTryout(['duration_minutes' => 10]);
        $this->makeQuestionPool($tryout, 5);
        $user = User::factory()->create(['role' => 'peserta']);

        $attempt = Attempt::create([
            'tryout_id' => $tryout->id,
            'user_id' => $user->id,
            'started_at' => now()->subMinutes(20), // duration already exceeded
            'status' => 'ongoing',
        ]);

        $question = $tryout->questionPool()->first();
        $attemptQuestion = $attempt->attemptQuestions()->create([
            'question_id' => $question->id,
            'display_order' => 1,
            'shuffled_option_order' => $question->options->pluck('id')->all(),
        ]);

        $optionId = $question->options()->first()->id;

        $this->expectException(\RuntimeException::class);
        app(AttemptService::class)->answer($attempt->refresh(), $attemptQuestion->id, $optionId);
    }

    public function test_visiting_exam_page_force_submits_expired_attempt_and_redirects(): void
    {
        $tryout = $this->makeTryout(['duration_minutes' => 10]);
        $this->makeQuestionPool($tryout, 5);
        $user = User::factory()->create(['role' => 'peserta']);

        $attempt = Attempt::create([
            'tryout_id' => $tryout->id,
            'user_id' => $user->id,
            'started_at' => now()->subMinutes(20),
            'status' => 'ongoing',
        ]);

        $question = $tryout->questionPool()->first();
        $attempt->attemptQuestions()->create([
            'question_id' => $question->id,
            'display_order' => 1,
            'shuffled_option_order' => $question->options->pluck('id')->all(),
        ]);

        Livewire::actingAs($user)
            ->test(ExamPage::class, ['tryout' => $tryout])
            ->assertRedirect(route('tryout.hasil', $tryout));

        $this->assertDatabaseHas('attempts', [
            'id' => $attempt->id,
            'status' => 'expired',
        ]);
    }

    public function test_ranking_is_hidden_until_tryout_closes(): void
    {
        $tryout = $this->makeTryout(['status' => 'published', 'ends_at' => now()->addDay()]);
        $questions = $this->makeQuestionPool($tryout, 5);
        $viewer = User::factory()->create(['role' => 'peserta']);

        Livewire::actingAs($viewer)
            ->test(RankingPage::class, ['tryout' => $tryout])
            ->assertSee('Peringkat akan tampil setelah tryout ditutup')
            ->assertDontSee('submitted_at_should_not_matter');

        $tryout->update(['status' => 'closed']);

        $finisher = User::factory()->create(['role' => 'peserta']);
        $attempt = Attempt::create([
            'tryout_id' => $tryout->id,
            'user_id' => $finisher->id,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'status' => 'submitted',
            'score' => 80,
        ]);

        Livewire::actingAs($viewer)
            ->test(RankingPage::class, ['tryout' => $tryout->refresh()])
            ->assertSee($finisher->name)
            ->assertSee('80');
    }
}
