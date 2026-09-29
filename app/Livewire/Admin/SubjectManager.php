<?php

namespace App\Livewire\Admin;

use App\Models\Subject;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SubjectManager extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $flash = null;

    public function create(): void
    {
        $this->reset(['editingId', 'name']);
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $subject = Subject::findOrFail($id);
        $this->editingId = $subject->id;
        $this->name = $subject->name;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->reset(['showForm', 'editingId', 'name']);
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects', 'name')->ignore($this->editingId),
            ],
        ]);

        if ($this->editingId) {
            Subject::findOrFail($this->editingId)->update($data);
            $this->flash = 'Mata pelajaran diperbarui.';
        } else {
            Subject::create($data);
            $this->flash = 'Mata pelajaran ditambahkan.';
        }

        $this->reset(['showForm', 'editingId', 'name']);
    }

    public function delete(int $id): void
    {
        $subject = Subject::findOrFail($id);

        if ($subject->questions()->exists() || $subject->tryouts()->exists()) {
            $this->flash = 'Tidak bisa menghapus: mata pelajaran ini masih dipakai oleh soal atau tryout.';

            return;
        }

        $subject->delete();
        $this->flash = 'Mata pelajaran dihapus.';
    }

    public function render()
    {
        return view('livewire.admin.subject-manager', [
            'subjects' => Subject::withCount(['questions', 'tryouts'])->orderBy('name')->get(),
        ]);
    }
}
