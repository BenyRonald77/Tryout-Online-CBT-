<?php

namespace Tests\Feature\Tryout;

use App\Models\Attempt;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Tryout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoSubmitCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_command_closes_out_expired_attempts_and_closes_passed_tryouts(): void
    {
        $subject = Subject::create(['name' => 'Sejarah Dasar']);
        $question = Question::create([
            'subject_id' => $subject->id,
            'body' => 'Contoh soal',
            'difficulty' => 'easy',
            'points' => 1,
        ]);
        $question->options()->createMany([
            ['body' => 'A', 'is_correct' => true],
            ['body' => 'B', 'is_correct' => false],
        ]);

        // Tryout whose window already passed, but admin never closed it manually.
        $tryout = Tryout::create([
            'title' => 'Tryout Yang Terlupa Ditutup',
            'duration_minutes' => 10,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
            'question_count' => 1,
            'status' => 'published',
        ]);
        $tryout->questionPool()->attach($question->id);

        $user = User::factory()->create(['role' => 'peserta']);

        // Attempt started well within the window but never finished; the
        // participant simply closed the tab. Time is long up now.
        $attempt = Attempt::create([
            'tryout_id' => $tryout->id,
            'user_id' => $user->id,
            'started_at' => now()->subDays(2),
            'status' => 'ongoing',
        ]);
        $attempt->attemptQuestions()->create([
            'question_id' => $question->id,
            'display_order' => 1,
            'shuffled_option_order' => $question->options->pluck('id')->all(),
        ]);

        $this->artisan('attempts:auto-submit-expired')->assertSuccessful();

        $attempt->refresh();
        $this->assertSame('expired', $attempt->status);
        $this->assertNotNull($attempt->submitted_at);
        $this->assertNotNull($attempt->score);

        $tryout->refresh();
        $this->assertSame('closed', $tryout->status);
    }
}
