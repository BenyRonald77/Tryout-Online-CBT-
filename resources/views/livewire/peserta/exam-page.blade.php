<div class="py-8" x-data>
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

        <div class="bg-white rounded-lg border border-gray-200 p-4 flex items-center justify-between sticky top-2 z-10">
            <div>
                <h1 class="font-semibold text-gray-900">{{ $tryout->title }}</h1>
                <p class="text-sm text-gray-500">Soal {{ $currentIndex + 1 }} dari {{ $questions->count() }}</p>
            </div>

            <div
                wire:ignore
                x-data="{
                    remaining: {{ $remainingSeconds }},
                    format(s) {
                        s = Math.max(0, s);
                        const m = Math.floor(s / 60).toString().padStart(2, '0');
                        const sec = Math.floor(s % 60).toString().padStart(2, '0');
                        return m + ':' + sec;
                    },
                }"
                x-init="
                    let interval = setInterval(() => { if (remaining > 0) remaining--; }, 1000);
                    Livewire.on('time-sync', (event) => { remaining = event.remaining; });
                "
                class="text-right"
            >
                <div
                    class="text-2xl font-bold tabular-nums"
                    :class="remaining <= 0 ? 'text-red-700' : (remaining <= 300 ? 'text-amber-700' : 'text-gray-900')"
                    x-text="format(remaining)"
                    aria-live="polite"
                    role="timer"
                ></div>
                <div class="text-xs text-gray-500">sisa waktu</div>
            </div>
        </div>

        <div wire:poll.10s="tick"></div>

        @if ($current)
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="prose prose-sm max-w-none text-gray-800 mb-5">
                    {!! $current->question->body !!}
                </div>

                <div class="space-y-2" aria-label="Pilihan jawaban">
                    @foreach ($current->orderedOptions() as $letterIndex => $option)
                        @php($letter = chr(65 + $letterIndex))
                        <button
                            type="button"
                            wire:click="selectOption({{ $current->id }}, {{ $option->id }})"
                            wire:key="option-{{ $current->id }}-{{ $option->id }}"
                            aria-pressed="{{ $selectedOptionId === $option->id ? 'true' : 'false' }}"
                            class="w-full text-left px-4 py-3 rounded-md border flex items-start gap-3 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-1 transition-colors
                                {{ $selectedOptionId === $option->id ? 'border-teal-600 bg-teal-50' : 'border-gray-200 hover:bg-gray-50' }}"
                        >
                            <span class="font-medium text-gray-500 shrink-0">{{ $letter }}.</span>
                            <span class="text-gray-800">{{ $option->body }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-4 text-sm" aria-live="polite">
                    @if ($saveState === 'saving')
                        <span class="text-gray-500">Menyimpan...</span>
                    @elseif ($saveState === 'saved')
                        <span class="text-green-700">Tersimpan</span>
                    @elseif ($saveState === 'error')
                        <span class="text-red-700">Gagal menyimpan: {{ $saveError }}</span>
                    @endif
                </div>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <button
                type="button"
                wire:click="previous"
                @disabled($currentIndex === 0)
                class="px-4 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-1"
            >
                Sebelumnya
            </button>

            <div class="flex flex-wrap gap-1 justify-center max-w-md">
                @foreach ($questions as $index => $aq)
                    <button
                        type="button"
                        wire:click="goToQuestion({{ $index }})"
                        wire:key="nav-{{ $aq->id }}"
                        aria-current="{{ $index === $currentIndex ? 'true' : 'false' }}"
                        aria-label="Ke soal {{ $index + 1 }}{{ $aq->selected_option_id ? ', sudah dijawab' : ', belum dijawab' }}"
                        class="w-8 h-8 text-xs font-medium rounded border focus:outline-none focus:ring-2 focus:ring-teal-500
                            {{ $index === $currentIndex ? 'border-teal-600 bg-teal-600 text-white' : ($aq->selected_option_id ? 'border-teal-300 bg-teal-50 text-teal-800' : 'border-gray-300 text-gray-600 hover:bg-gray-50') }}"
                    >
                        {{ $index + 1 }}
                    </button>
                @endforeach
            </div>

            @if ($currentIndex === $questions->count() - 1)
                <button
                    type="button"
                    wire:click="submit"
                    wire:confirm="Yakin ingin menyelesaikan tryout ini? Jawaban tidak bisa diubah lagi setelah submit."
                    class="px-4 py-2 text-sm font-medium rounded-md bg-teal-700 text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-1"
                >
                    Selesai &amp; Submit
                </button>
            @else
                <button
                    type="button"
                    wire:click="next"
                    class="px-4 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-1"
                >
                    Selanjutnya
                </button>
            @endif
        </div>

        <div class="text-center">
            <button type="button" wire:click="submit" wire:confirm="Yakin ingin menyelesaikan tryout ini sekarang? Jawaban tidak bisa diubah lagi setelah submit." class="text-sm text-gray-500 hover:text-gray-700 underline">
                Selesaikan sekarang (submit lebih awal)
            </button>
        </div>
    </div>
</div>
