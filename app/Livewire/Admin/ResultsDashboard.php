<?php

namespace App\Livewire\Admin;

use App\Models\Tryout;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ResultsDashboard extends Component
{
    public ?int $selectedTryoutId = null;

    public function mount(): void
    {
        $this->selectedTryoutId = Tryout::orderByDesc('id')->value('id');
    }

    public function render()
    {
        $tryout = $this->selectedTryoutId ? Tryout::find($this->selectedTryoutId) : null;

        $attempts = $tryout
            ? $tryout->attempts()->with('user')->orderByDesc('score')->orderBy('submitted_at')->get()
            : collect();

        return view('livewire.admin.results-dashboard', [
            'tryouts' => Tryout::orderByDesc('id')->get(),
            'tryout' => $tryout,
            'attempts' => $attempts,
        ]);
    }
}
