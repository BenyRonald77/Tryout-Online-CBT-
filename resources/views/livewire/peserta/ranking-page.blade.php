<div>
    <div>
        <div class="mb-6">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Peringkat: {{ $tryout->title }}</h2>
        </div>
    </div>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if (! $tryout->isClosed())
                <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
                    <p class="text-gray-700 font-medium">Peringkat akan tampil setelah tryout ditutup.</p>
                    <p class="text-sm text-gray-500 mt-1">
                        Tryout ini dijadwalkan tutup pada {{ $tryout->ends_at->format('d M Y H:i') }}
                        @if (auth()->user()?->isAdmin())
                            , atau admin bisa menutupnya lebih awal secara manual.
                        @endif
                    </p>
                </div>
            @elseif ($ranking->isEmpty())
                <div class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-600">
                    Tryout ini sudah ditutup, tapi belum ada peserta yang menyelesaikannya.
                </div>
            @else
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-left">
                            <tr>
                                <th class="px-4 py-3 w-16">Peringkat</th>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3 text-right">Skor</th>
                                <th class="px-4 py-3 text-right">Selesai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($ranking as $index => $attempt)
                                <tr class="{{ auth()->id() === $attempt->user_id ? 'bg-teal-50' : '' }}">
                                    <td class="px-4 py-3 font-semibold text-gray-700">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 text-gray-800">
                                        {{ $attempt->user->name }}
                                        @if (auth()->id() === $attempt->user_id)
                                            <span class="text-xs text-teal-700">(Anda)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900">{{ number_format($attempt->score, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-500">{{ $attempt->submitted_at?->format('d M H:i:s') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
