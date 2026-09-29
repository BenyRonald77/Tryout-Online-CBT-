<div>
    <div class="mb-6">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kelola Bank Soal</h2>
    </div>

    <div class="py-4">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <x-admin.tabs />

            @if ($flash)
                <div class="bg-teal-50 border border-teal-200 text-teal-800 rounded-md px-4 py-3 text-sm">
                    {{ $flash }}
                </div>
            @endif

            <div class="flex justify-between items-center gap-3">
                <div>
                    <label for="filterSubjectId" class="sr-only">Filter mata pelajaran</label>
                    <select wire:model.live="filterSubjectId" id="filterSubjectId" class="rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                        <option value="">Semua Mata Pelajaran</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if (! $showForm)
                    <button wire:click="create" class="px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-md hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-1">
                        Tambah Soal
                    </button>
                @endif
            </div>

            @if ($showForm)
                <form wire:submit="save" class="bg-white border border-gray-200 rounded-lg p-5 space-y-4">
                    <div>
                        <x-input-label for="subject_id" value="Mata Pelajaran" />
                        <select wire:model="subject_id" id="subject_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="">Pilih mata pelajaran</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('subject_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="body" value="Pertanyaan" />
                        <textarea wire:model="body" id="body" rows="3" class="mt-1 block w-full rounded-md border-gray-300 focus:border-teal-500 focus:ring-teal-500"></textarea>
                        <x-input-error :messages="$errors->get('body')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="difficulty" value="Tingkat Kesulitan" />
                            <select wire:model="difficulty" id="difficulty" class="mt-1 block w-full rounded-md border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                                <option value="easy">Mudah</option>
                                <option value="medium">Sedang</option>
                                <option value="hard">Sulit</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="points" value="Poin" />
                            <x-text-input wire:model="points" id="points" type="number" min="1" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Pilihan Jawaban (pilih tombol radio untuk menandai jawaban benar)" />
                        <x-input-error :messages="$errors->get('options')" class="mt-2" />

                        <div class="space-y-2 mt-2">
                            @foreach ($options as $index => $option)
                                <div class="flex items-center gap-2" wire:key="option-row-{{ $index }}">
                                    <input
                                        type="radio"
                                        name="correct_option"
                                        wire:click="markCorrect({{ $index }})"
                                        @checked($option['is_correct'])
                                        aria-label="Tandai pilihan {{ $index + 1 }} sebagai jawaban benar"
                                        class="text-teal-600 focus:ring-teal-500"
                                    />
                                    <input
                                        type="text"
                                        wire:model="options.{{ $index }}.body"
                                        placeholder="Teks pilihan jawaban {{ chr(65 + $index) }}"
                                        class="flex-1 rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                                    />
                                    <button
                                        type="button"
                                        wire:click="removeOption({{ $index }})"
                                        class="text-red-600 hover:text-red-800 text-sm px-2"
                                        aria-label="Hapus pilihan {{ $index + 1 }}"
                                        @if (count($options) <= 2) disabled @endif
                                    >
                                        &times;
                                    </button>
                                </div>
                                <x-input-error :messages="$errors->get('options.'.$index.'.body')" class="mt-1" />
                            @endforeach
                        </div>

                        <button type="button" wire:click="addOption" class="mt-2 text-sm text-teal-700 hover:text-teal-900 underline" @if (count($options) >= 6) disabled @endif>
                            + Tambah Pilihan
                        </button>
                    </div>

                    <div class="flex gap-2">
                        <x-primary-button type="submit">Simpan Soal</x-primary-button>
                        <x-secondary-button type="button" wire:click="cancel">Batal</x-secondary-button>
                    </div>
                </form>
            @endif

            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                @if ($questions->isEmpty())
                    <div class="p-8 text-center text-gray-600">
                        Belum ada soal untuk filter ini.
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-gray-600">
                            <tr>
                                <th class="px-4 py-3">Pertanyaan</th>
                                <th class="px-4 py-3">Mapel</th>
                                <th class="px-4 py-3">Tingkat</th>
                                <th class="px-4 py-3">Poin</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($questions as $question)
                                <tr wire:key="question-{{ $question->id }}">
                                    <td class="px-4 py-3 text-gray-800">{{ Str::limit(strip_tags($question->body), 80) }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $question->subject?->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $question->difficulty }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $question->points }}</td>
                                    <td class="px-4 py-3 text-right space-x-3">
                                        <button wire:click="edit({{ $question->id }})" class="text-teal-700 hover:text-teal-900 underline text-sm">Ubah</button>
                                        <button
                                            wire:click="delete({{ $question->id }})"
                                            wire:confirm="Hapus soal ini?"
                                            class="text-red-700 hover:text-red-900 underline text-sm"
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>
