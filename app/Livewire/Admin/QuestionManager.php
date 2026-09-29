<?php

namespace App\Livewire\Admin;

use App\Models\AttemptQuestion;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class QuestionManager extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $subject_id = null;

    public string $body = '';

    public string $difficulty = 'medium';

    public int $points = 1;

    /** @var array<int, array{id: ?int, body: string, is_correct: bool}> */
    public array $options = [];

    public ?int $filterSubjectId = null;

    public ?string $flash = null;

    public function mount(): void
    {
        $this->resetOptions();
    }

    protected function resetOptions(): void
    {
        $this->options = [
            ['id' => null, 'body' => '', 'is_correct' => true],
            ['id' => null, 'body' => '', 'is_correct' => false],
            ['id' => null, 'body' => '', 'is_correct' => false],
            ['id' => null, 'body' => '', 'is_correct' => false],
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'subject_id', 'body', 'difficulty', 'points']);
        $this->resetOptions();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $question = Question::with('options')->findOrFail($id);

        $this->editingId = $question->id;
        $this->subject_id = $question->subject_id;
        $this->body = $question->body;
        $this->difficulty = $question->difficulty;
        $this->points = $question->points;
        $this->options = $question->options->map(fn ($o) => [
            'id' => $o->id,
            'body' => $o->body,
            'is_correct' => $o->is_correct,
        ])->all();

        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->reset(['showForm', 'editingId', 'subject_id', 'body', 'difficulty', 'points']);
        $this->resetOptions();
    }

    public function addOption(): void
    {
        if (count($this->options) >= 6) {
            return;
        }

        $this->options[] = ['id' => null, 'body' => '', 'is_correct' => false];
    }

    public function removeOption(int $index): void
    {
        if (count($this->options) <= 2) {
            return;
        }

        $wasCorrect = $this->options[$index]['is_correct'] ?? false;
        unset($this->options[$index]);
        $this->options = array_values($this->options);

        if ($wasCorrect && count($this->options) > 0) {
            $this->options[0]['is_correct'] = true;
        }
    }

    public function markCorrect(int $index): void
    {
        foreach ($this->options as $i => $option) {
            $this->options[$i]['is_correct'] = ($i === $index);
        }
    }

    public function save(): void
    {
        $this->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'body' => ['required', 'string', 'min:3'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'points' => ['required', 'integer', 'min:1'],
            'options' => ['array', 'min:2'],
            'options.*.body' => ['required', 'string', 'max:500'],
        ], [
            'options.min' => 'Soal harus punya minimal 2 pilihan jawaban.',
        ]);

        $correctCount = collect($this->options)->where('is_correct', true)->count();

        if ($correctCount !== 1) {
            $this->addError('options', 'Harus ada tepat satu pilihan jawaban yang benar.');

            return;
        }

        DB::transaction(function () {
            $question = Question::updateOrCreate(
                ['id' => $this->editingId],
                [
                    'subject_id' => $this->subject_id,
                    'body' => $this->body,
                    'difficulty' => $this->difficulty,
                    'points' => $this->points,
                ]
            );

            $keptIds = [];

            foreach ($this->options as $option) {
                $saved = $question->options()->updateOrCreate(
                    ['id' => $option['id']],
                    [
                        'body' => $option['body'],
                        'is_correct' => (bool) $option['is_correct'],
                    ]
                );
                $keptIds[] = $saved->id;
            }

            $question->options()->whereNotIn('id', $keptIds)->delete();
        });

        $this->flash = $this->editingId ? 'Soal diperbarui.' : 'Soal ditambahkan.';
        $this->cancel();
    }

    public function delete(int $id): void
    {
        $question = Question::findOrFail($id);

        if (AttemptQuestion::where('question_id', $question->id)->exists()) {
            $this->flash = 'Tidak bisa menghapus: soal ini sudah pernah dipakai peserta dalam attempt.';

            return;
        }

        $question->delete();
        $this->flash = 'Soal dihapus.';
    }

    public function render()
    {
        $questions = Question::with(['subject', 'options'])
            ->when($this->filterSubjectId, fn ($q) => $q->where('subject_id', $this->filterSubjectId))
            ->orderByDesc('id')
            ->get();

        return view('livewire.admin.question-manager', [
            'questions' => $questions,
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }
}
