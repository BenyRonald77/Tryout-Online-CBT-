<?php

namespace App\Livewire\Admin;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Tryout;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TryoutManager extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public ?int $subject_id = null;

    public int $duration_minutes = 60;

    public string $starts_at = '';

    public string $ends_at = '';

    public int $question_count = 10;

    public ?int $managingPoolFor = null;

    public ?int $poolFilterSubjectId = null;

    public ?string $flash = null;

    public function create(): void
    {
        $this->reset(['editingId', 'title', 'subject_id', 'starts_at', 'ends_at']);
        $this->duration_minutes = 60;
        $this->question_count = 10;
        $this->showForm = true;
        $this->managingPoolFor = null;
    }

    public function edit(int $id): void
    {
        $tryout = Tryout::findOrFail($id);
        $this->editingId = $tryout->id;
        $this->title = $tryout->title;
        $this->subject_id = $tryout->subject_id;
        $this->duration_minutes = $tryout->duration_minutes;
        $this->starts_at = $tryout->starts_at->format('Y-m-d\TH:i');
        $this->ends_at = $tryout->ends_at->format('Y-m-d\TH:i');
        $this->question_count = $tryout->question_count;
        $this->showForm = true;
        $this->managingPoolFor = null;
    }

    public function cancel(): void
    {
        $this->reset(['showForm', 'editingId', 'title', 'subject_id', 'starts_at', 'ends_at']);
        $this->duration_minutes = 60;
        $this->question_count = 10;
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'question_count' => ['required', 'integer', 'min:1'],
        ]);

        if ($this->editingId) {
            Tryout::findOrFail($this->editingId)->update($data);
            $this->flash = 'Tryout diperbarui.';
        } else {
            $data['status'] = 'draft';
            Tryout::create($data);
            $this->flash = 'Tryout dibuat sebagai draft. Tambahkan pool soal sebelum dipublikasikan.';
        }

        $this->cancel();
    }

    public function delete(int $id): void
    {
        $tryout = Tryout::findOrFail($id);

        if ($tryout->attempts()->exists()) {
            $this->flash = 'Tidak bisa menghapus: tryout ini sudah punya attempt peserta.';

            return;
        }

        $tryout->delete();
        $this->flash = 'Tryout dihapus.';
    }

    public function publish(int $id): void
    {
        $tryout = Tryout::findOrFail($id);
        $poolCount = $tryout->questionPool()->count();

        if ($poolCount < $tryout->question_count) {
            $this->flash = "Belum bisa dipublikasikan: pool soal baru {$poolCount}, dibutuhkan minimal {$tryout->question_count}.";

            return;
        }

        $tryout->update(['status' => 'published']);
        $this->flash = 'Tryout dipublikasikan.';
    }

    public function close(int $id): void
    {
        Tryout::findOrFail($id)->update(['status' => 'closed']);
        $this->flash = 'Tryout ditutup manual. Peringkat sekarang bisa dilihat peserta.';
    }

    public function managePool(int $id): void
    {
        $this->managingPoolFor = $id;
        $this->showForm = false;
    }

    public function stopManagingPool(): void
    {
        $this->managingPoolFor = null;
    }

    public function togglePoolQuestion(int $questionId): void
    {
        $tryout = Tryout::findOrFail($this->managingPoolFor);

        if ($tryout->questionPool()->where('questions.id', $questionId)->exists()) {
            $tryout->questionPool()->detach($questionId);
        } else {
            $tryout->questionPool()->attach($questionId);
        }
    }

    public function render()
    {
        $tryouts = Tryout::with('subject')
            ->withCount(['questionPool', 'attempts'])
            ->orderByDesc('id')
            ->get();

        $poolQuestions = null;
        $poolTryout = null;

        if ($this->managingPoolFor) {
            $poolTryout = Tryout::findOrFail($this->managingPoolFor);
            $poolQuestions = Question::with('subject')
                ->when($this->poolFilterSubjectId, fn ($q) => $q->where('subject_id', $this->poolFilterSubjectId))
                ->orderByDesc('id')
                ->get();
        }

        return view('livewire.admin.tryout-manager', [
            'tryouts' => $tryouts,
            'subjects' => Subject::orderBy('name')->get(),
            'poolTryout' => $poolTryout,
            'poolQuestions' => $poolQuestions,
            'poolQuestionIds' => $poolTryout ? $poolTryout->questionPool()->pluck('questions.id')->all() : [],
        ]);
    }
}
