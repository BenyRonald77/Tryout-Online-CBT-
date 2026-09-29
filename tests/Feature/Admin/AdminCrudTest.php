<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\QuestionManager;
use App\Livewire\Admin\SubjectManager;
use App\Livewire\Admin\TryoutManager;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Tryout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function peserta(): User
    {
        return User::factory()->create(['role' => 'peserta']);
    }

    public function test_peserta_cannot_access_admin_pages(): void
    {
        $this->actingAs($this->peserta())
            ->get('/admin/mata-pelajaran')
            ->assertForbidden();
    }

    public function test_guest_cannot_access_admin_pages(): void
    {
        $this->get('/admin/mata-pelajaran')->assertRedirect('/login');
    }

    public function test_admin_can_create_update_and_delete_a_subject(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(SubjectManager::class)
            ->call('create')
            ->set('name', 'Fisika Dasar')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('subjects', ['name' => 'Fisika Dasar']);

        $subject = Subject::where('name', 'Fisika Dasar')->first();

        Livewire::actingAs($admin)
            ->test(SubjectManager::class)
            ->call('edit', $subject->id)
            ->set('name', 'Fisika Dasar (Revisi)')
            ->call('save');

        $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'name' => 'Fisika Dasar (Revisi)']);

        Livewire::actingAs($admin)
            ->test(SubjectManager::class)
            ->call('delete', $subject->id);

        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    public function test_admin_can_create_a_question_with_options_and_exactly_one_correct_answer(): void
    {
        $admin = $this->admin();
        $subject = Subject::create(['name' => 'Kimia Dasar']);

        Livewire::actingAs($admin)
            ->test(QuestionManager::class)
            ->call('create')
            ->set('subject_id', $subject->id)
            ->set('body', 'Rumus kimia air adalah?')
            ->set('difficulty', 'easy')
            ->set('points', 2)
            ->set('options.0.body', 'H2O')
            ->set('options.1.body', 'CO2')
            ->set('options.2.body', 'O2')
            ->set('options.3.body', 'NaCl')
            ->call('markCorrect', 0)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('questions', ['body' => 'Rumus kimia air adalah?', 'points' => 2]);

        $question = Question::where('body', 'Rumus kimia air adalah?')->first();
        $this->assertCount(4, $question->options);
        $this->assertSame(1, $question->options->where('is_correct', true)->count());
        $this->assertSame('H2O', $question->options->firstWhere('is_correct', true)->body);
    }

    public function test_admin_can_build_a_tryout_manage_its_pool_and_publish_it(): void
    {
        $admin = $this->admin();
        $subject = Subject::create(['name' => 'Biologi Dasar']);

        $questions = collect(range(1, 5))->map(function (int $i) use ($subject) {
            $q = Question::create([
                'subject_id' => $subject->id,
                'body' => "Soal biologi {$i}",
                'difficulty' => 'easy',
                'points' => 1,
            ]);
            $q->options()->createMany([
                ['body' => 'A', 'is_correct' => true],
                ['body' => 'B', 'is_correct' => false],
            ]);

            return $q;
        });

        Livewire::actingAs($admin)
            ->test(TryoutManager::class)
            ->call('create')
            ->set('title', 'Tryout Biologi Percobaan')
            ->set('duration_minutes', 40)
            ->set('question_count', 5)
            ->set('starts_at', now()->format('Y-m-d\TH:i'))
            ->set('ends_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        $tryout = Tryout::where('title', 'Tryout Biologi Percobaan')->first();
        $this->assertSame('draft', $tryout->status);

        // Cannot publish yet: pool is empty, below question_count.
        Livewire::actingAs($admin)
            ->test(TryoutManager::class)
            ->call('publish', $tryout->id);

        $this->assertSame('draft', $tryout->refresh()->status);

        // Attach the pool via the pool-management panel.
        $component = Livewire::actingAs($admin)->test(TryoutManager::class)->call('managePool', $tryout->id);

        foreach ($questions as $question) {
            $component->call('togglePoolQuestion', $question->id);
        }

        $this->assertSame(5, $tryout->questionPool()->count());

        Livewire::actingAs($admin)
            ->test(TryoutManager::class)
            ->call('publish', $tryout->id);

        $this->assertSame('published', $tryout->refresh()->status);

        Livewire::actingAs($admin)
            ->test(TryoutManager::class)
            ->call('close', $tryout->id);

        $this->assertSame('closed', $tryout->refresh()->status);
    }
}
