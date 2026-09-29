<div>
    <div class="mb-6">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kelola Tryout</h2>
    </div>

    <div class="py-4">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <x-admin.tabs />

            @if ($flash)
                <div class="bg-teal-50 border border-teal-200 text-teal-800 rounded-md px-4 py-3 text-sm">
                    {{ $flash }}
                </div>
            @endif

            <div class="flex justify-end">
                @if (! $showForm && ! $managingPoolFor)
                    <button wire:click="create" class="px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-md hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-1">
                        Buat Tryout Baru
                    </button>
                @endif
            </div>

            @if ($showForm)
                <form wire:submit="save" class="bg-white border border-gray-200 rounded-lg p-5 space-y-4">
                    <div>
                        <x-input-label for="title" value="Judul Tryout" />
                        <x-text-input wire:model="title" id="title" type="text" class="mt-1 block w-full" autofocus />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="subject_id" value="Mata Pelajaran (opsional, kosongkan jika campuran)" />
                        <select wire:model="subject_id" id="subject_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="">Campuran / lintas mapel</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="duration_minutes" value="Durasi (menit)" />
                            <x-text-input wire:model="duration_minutes" id="duration_minutes" type="number" min="1" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('duration_minutes')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="question_count" value="Jumlah Soal per Peserta" />
                            <x-text-input wire:model="question_count" id="question_count" type="number" min="1" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('question_count')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="starts_at" value="Mulai Dibuka" />
                            <x-text-input wire:model="starts_at" id="starts_at" type="datetime-local" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="ends_at" value="Ditutup Pada" />
                            <x-text-input wire:model="ends_at" id="ends_at" type="datetime-local" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('ends_at')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <x-primary-button type="submit">Simpan Tryout</x-primary-button>
                        <x-secondary-button type="button" wire:click="cancel">Batal</x-secondary-button>
                    </div>
                </form>
            @endif

            @if ($managingPoolFor && $poolTryout)
                <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium text-gray-800">Pool Soal untuk: {{ $poolTryout->title }}</h3>
                        <button wire:click="stopManagingPool" class="text-sm text-gray-500 hover:text-gray-700 underline">Tutup</button>
                    </div>
                    <p class="text-sm text-gray-600">
                        Dipilih: {{ count($poolQuestionIds) }} soal. Dibutuhkan minimal {{ $poolTryout->question_count }} soal agar bisa dipublikasikan
                        (peserta akan menerima {{ $poolTryout->question_count }} soal acak dari pool ini).
                    </p>

                    <select wire:model.live="poolFilterSubjectId" class="rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                        <option value="">Semua Mata Pelajaran</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>

                    <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto border border-gray-100 rounded-md">
                        @forelse ($poolQuestions as $question)
                            <label class="flex items-start gap-3 p-3 hover:bg-gray-50 cursor-pointer" wire:key="pool-q-{{ $question->id }}">
                                <input
                                    type="checkbox"
                                    wire:click="togglePoolQuestion({{ $question->id }})"
                                    @checked(in_array($question->id, $poolQuestionIds))
                                    class="mt-1 text-teal-600 focus:ring-teal-500"
                                />
                                <span class="text-sm text-gray-800">
                                    {{ Str::limit(strip_tags($question->body), 100) }}
                                    <span class="text-gray-500">({{ $question->subject?->name }})</span>
                                </span>
                            </label>
                        @empty
                            <div class="p-4 text-sm text-gray-500">Tidak ada soal untuk filter ini. Tambahkan soal dulu di Bank Soal.</div>
                        @endforelse
                    </div>
                </div>
            @endif

            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                @if ($tryouts->isEmpty())
                    <div class="p-8 text-center text-gray-600">
                        Belum ada tryout. Buat yang pertama di atas.
                    </div>
                @else
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-gray-600">
                            <tr>
                                <th class="px-4 py-3">Judul</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Pool</th>
                                <th class="px-4 py-3">Attempt</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($tryouts as $tryout)
                                <tr wire:key="tryout-{{ $tryout->id }}">
                                    <td class="px-4 py-3 text-gray-800">
                                        {{ $tryout->title }}
                                        <div class="text-xs text-gray-500">{{ $tryout->starts_at->format('d M Y H:i') }} - {{ $tryout->ends_at->format('d M Y H:i') }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($tryout->status === 'draft')
                                            <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700">Draft</span>
                                        @elseif ($tryout->status === 'published')
                                            <span class="text-xs px-2 py-0.5 rounded bg-teal-50 text-teal-700">Published</span>
                                        @else
                                            <span class="text-xs px-2 py-0.5 rounded bg-gray-200 text-gray-700">Closed</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $tryout->question_pool_count }} / {{ $tryout->question_count }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $tryout->attempts_count }}</td>
                                    <td class="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                                        <button wire:click="managePool({{ $tryout->id }})" class="text-teal-700 hover:text-teal-900 underline text-sm">Pool Soal</button>
                                        <button wire:click="edit({{ $tryout->id }})" class="text-teal-700 hover:text-teal-900 underline text-sm">Ubah</button>
                                        @if ($tryout->status === 'draft')
                                            <button wire:click="publish({{ $tryout->id }})" class="text-green-700 hover:text-green-900 underline text-sm">Publish</button>
                                        @elseif ($tryout->status === 'published')
                                            <button wire:click="close({{ $tryout->id }})" wire:confirm="Tutup tryout ini sekarang? Peringkat akan langsung tampil." class="text-amber-700 hover:text-amber-900 underline text-sm">Tutup</button>
                                        @endif
                                        <a href="{{ route('tryout.ranking', $tryout) }}" wire:navigate class="text-gray-600 hover:text-gray-900 underline text-sm">Peringkat</a>
                                        @if ($tryout->attempts_count === 0)
                                            <button wire:click="delete({{ $tryout->id }})" wire:confirm="Hapus tryout ini?" class="text-red-700 hover:text-red-900 underline text-sm">Hapus</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
