<?php

namespace App\Livewire\Peserta;

use App\Models\Attempt;
use App\Models\Tryout;
use App\Services\AttemptService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

#[Layout('layouts.app')]
class ExamPage extends Component
{
    public Tryout $tryout;

    public Attempt $attempt;

    public int $currentIndex = 0;

    public ?int $selectedOptionId = null;

    /** idle | saving | saved | error */
    public string $saveState = 'idle';

    public string $saveError = '';

    public int $remainingSeconds = 0;

    public function mount(Tryout $tryout, AttemptService $attemptService): void
    {
        $this->tryout = $tryout;

        $attempt = $attemptService->findAttemptOrFail($tryout, auth()->user());
        $attempt = $attemptService->forceSubmitIfExpired($attempt);

        if (! $attempt->isOngoing()) {
            $this->redirectRoute('tryout.hasil', $tryout);

            return;
        }

        $this->attempt = $attempt;
        $this->remainingSeconds = $attempt->remainingSeconds();
        $this->loadSelectedOption();
    }

    /**
     * @return Collection<int, \App\Models\AttemptQuestion>
     */
    public function questions(): Collection
    {
        return $this->attempt->attemptQuestions()->with('question.options')->get();
    }

    public function currentQuestion(): ?\App\Models\AttemptQuestion
    {
        return $this->questions()->get($this->currentIndex);
    }

    protected function loadSelectedOption(): void
    {
        $this->selectedOptionId = $this->currentQuestion()?->selected_option_id;
        $this->saveState = 'idle';
    }

    public function goToQuestion(int $index): void
    {
        $count = $this->questions()->count();

        if ($index < 0 || $index >= $count) {
            return;
        }

        $this->currentIndex = $index;
        $this->loadSelectedOption();
    }

    public function previous(): void
    {
        $this->goToQuestion($this->currentIndex - 1);
    }

    public function next(): void
    {
        $this->goToQuestion($this->currentIndex + 1);
    }

    /**
     * Fires immediately when the participant picks an option. This is the
     * autosave: no separate "save" button, no polling needed for it to
     * persist. Every call re-checks server time first, so a client racing
     * against the deadline can never sneak an answer in after time is up.
     */
    public function selectOption(int $attemptQuestionId, int $optionId): void
    {
        $attemptService = app(AttemptService::class);

        $this->attempt->refresh();
        $this->attempt = $attemptService->forceSubmitIfExpired($this->attempt);

        if (! $this->attempt->isOngoing()) {
            $this->redirectRoute('tryout.hasil', $this->tryout);

            return;
        }

        $this->saveState = 'saving';

        try {
            $attemptService->answer($this->attempt, $attemptQuestionId, $optionId);
            $this->selectedOptionId = $optionId;
            $this->saveState = 'saved';
            $this->saveError = '';
        } catch (RuntimeException $e) {
            $this->saveState = 'error';
            $this->saveError = $e->getMessage();
        }

        $this->remainingSeconds = $this->attempt->remainingSeconds();
    }

    /**
     * Runs on every poll tick (client-side JS clock resync, see the view).
     * Re-derives remaining time from the database and force-closes the
     * attempt if time already ran out, even if the participant never
     * touched anything.
     */
    public function tick(): void
    {
        $attemptService = app(AttemptService::class);

        $this->attempt->refresh();
        $this->attempt = $attemptService->forceSubmitIfExpired($this->attempt);

        if (! $this->attempt->isOngoing()) {
            $this->redirectRoute('tryout.hasil', $this->tryout);

            return;
        }

        $this->remainingSeconds = $this->attempt->remainingSeconds();
        $this->dispatch('time-sync', remaining: $this->remainingSeconds);
    }

    public function submit(): void
    {
        $attemptService = app(AttemptService::class);

        $this->attempt->refresh();

        if ($this->attempt->isOngoing()) {
            $attemptService->finalize($this->attempt, 'submitted');
        }

        $this->redirectRoute('tryout.hasil', $this->tryout);
    }

    public function render()
    {
        return view('livewire.peserta.exam-page', [
            'questions' => $this->questions(),
            'current' => $this->currentQuestion(),
        ]);
    }
}
