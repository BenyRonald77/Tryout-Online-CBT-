<?php

namespace Tests\Feature\Tryout;

use App\Models\Attempt;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Tryout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TryoutIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/tryout')->assertRedirect('/login');
    }

    public function test_peserta_sees_the_tryout_list_with_a_working_start_button(): void
    {
        $subject = Subject::create(['name' => 'Sejarah']);
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

        $tryout = Tryout::create([
            'title' => 'Tryout Terbuka Untuk Semua',
            'duration_minutes' => 20,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'question_count' => 1,
            'status' => 'published',
        ]);
        $tryout->questionPool()->attach($question->id);

        $user = User::factory()->create(['role' => 'peserta']);

        $this->actingAs($user)
            ->get('/tryout')
            ->assertOk()
            ->assertSee('Tryout Terbuka Untuk Semua')
            ->assertSee('Mulai Tryout');

        $this->actingAs($user)
            ->post(route('tryout.mulai', $tryout))
            ->assertRedirect(route('tryout.kerjakan', $tryout));

        $this->assertDatabaseHas('attempts', [
            'tryout_id' => $tryout->id,
            'user_id' => $user->id,
            'status' => 'ongoing',
        ]);
    }

    public function test_peserta_sees_empty_state_when_no_tryouts_published(): void
    {
        $user = User::factory()->create(['role' => 'peserta']);

        $this->actingAs($user)
            ->get('/tryout')
            ->assertOk()
            ->assertSee('Belum ada tryout yang dipublikasikan');
    }

    public function test_peserta_can_view_their_own_result_page(): void
    {
        $subject = Subject::create(['name' => 'Geografi']);
        $tryout = Tryout::create([
            'title' => 'Tryout Sudah Selesai',
            'duration_minutes' => 20,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
            'question_count' => 1,
            'status' => 'closed',
        ]);
        $user = User::factory()->create(['role' => 'peserta']);

        Attempt::create([
            'tryout_id' => $tryout->id,
            'user_id' => $user->id,
            'started_at' => now()->subDays(2),
            'submitted_at' => now()->subDays(2)->addMinutes(10),
            'status' => 'submitted',
            'score' => 88.5,
        ]);

        $this->actingAs($user)
            ->get(route('tryout.hasil', $tryout))
            ->assertOk()
            ->assertSee('88.5');
    }
}
