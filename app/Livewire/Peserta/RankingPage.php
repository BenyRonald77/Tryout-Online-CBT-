<?php

namespace App\Livewire\Peserta;

use App\Models\Tryout;
use App\Services\TryoutService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class RankingPage extends Component
{
    public Tryout $tryout;

    public function mount(Tryout $tryout, TryoutService $tryoutService): void
    {
        $this->tryout = $tryoutService->closeIfPassed($tryout);
    }

    /**
     * National ranking: every finalized attempt (submitted or expired,
     * i.e. graded) for this tryout, ordered by score desc, then by
     * submitted_at asc as the tie-break (finishing faster wins ties).
     *
     * @return Collection<int, \App\Models\Attempt>
     */
    public function ranking(): Collection
    {
        if (! $this->tryout->isClosed()) {
            return collect();
        }

        return $this->tryout->attempts()
            ->whereIn('status', ['submitted', 'expired'])
            ->with('user')
            ->orderByDesc('score')
            ->orderBy('submitted_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.peserta.ranking-page', [
            'ranking' => $this->ranking(),
        ]);
    }
}
