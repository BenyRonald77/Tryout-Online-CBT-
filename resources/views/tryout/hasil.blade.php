<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Hasil: {{ $tryout->title }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-baseline gap-3">
                    <span class="text-4xl font-bold text-gray-900">{{ number_format($attempt->score, 2) }}</span>
                    <span class="text-gray-500">/ 100</span>
                </div>
                <p class="text-sm text-gray-600 mt-2">
                    Status:
                    @if ($attempt->status === 'submitted')
                        <span class="text-green-700 font-medium">Selesai (disubmit sendiri)</span>
                    @else
                        <span class="text-amber-700 font-medium">Selesai otomatis (waktu habis)</span>
                    @endif
                </p>
                <p class="text-sm text-gray-600">
                    Mulai: {{ $attempt->started_at->format('d M Y H:i:s') }} &middot;
                    Selesai: {{ $attempt->submitted_at?->format('d M Y H:i:s') }}
                </p>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 divide-y divide-gray-100">
                <div class="p-4 font-medium text-gray-700 text-sm">Rincian Jawaban</div>
                @foreach ($attempt->attemptQuestions as $aq)
                    <div class="p-4 flex items-start justify-between gap-4">
                        <div class="text-sm text-gray-800">
                            <span class="font-medium">Soal {{ $aq->display_order }}.</span>
                            {{ Str::limit(strip_tags($aq->question->body), 120) }}
                        </div>
                        <div class="shrink-0">
                            @if ($aq->selected_option_id === null)
                                <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-600">Tidak dijawab</span>
                            @elseif ($aq->is_correct)
                                <span class="text-xs px-2 py-0.5 rounded bg-green-50 text-green-700">Benar</span>
                            @else
                                <span class="text-xs px-2 py-0.5 rounded bg-red-50 text-red-700">Salah</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center gap-4">
                <a href="{{ route('tryout.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">Kembali ke Daftar Tryout</a>
                @if ($tryout->status === 'closed')
                    <a href="{{ route('tryout.ranking', $tryout) }}" class="text-sm text-teal-700 hover:text-teal-800 underline">Lihat Peringkat</a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
