<div>
    <div class="mb-6">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard Hasil</h2>
    </div>

    <div class="py-4">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <x-admin.tabs />

            @if ($tryouts->isEmpty())
                <div class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-600">
                    Belum ada tryout yang dibuat.
                </div>
            @else
                <select wire:model.live="selectedTryoutId" class="rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                    @foreach ($tryouts as $t)
                        <option value="{{ $t->id }}">{{ $t->title }}</option>
                    @endforeach
                </select>

                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    @if ($attempts->isEmpty())
                        <div class="p-8 text-center text-gray-600">
                            Belum ada peserta yang mengerjakan tryout ini.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-left text-gray-600">
                                <tr>
                                    <th class="px-4 py-3">Peserta</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Skor</th>
                                    <th class="px-4 py-3">Mulai</th>
                                    <th class="px-4 py-3">Selesai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($attempts as $attempt)
                                    <tr wire:key="attempt-{{ $attempt->id }}">
                                        <td class="px-4 py-3 text-gray-800">{{ $attempt->user->name }}</td>
                                        <td class="px-4 py-3">
                                            @if ($attempt->status === 'ongoing')
                                                <span class="text-xs px-2 py-0.5 rounded bg-amber-50 text-amber-700">Sedang Mengerjakan</span>
                                            @elseif ($attempt->status === 'submitted')
                                                <span class="text-xs px-2 py-0.5 rounded bg-green-50 text-green-700">Submitted</span>
                                            @else
                                                <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700">Expired</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900">
                                            {{ $attempt->score !== null ? number_format($attempt->score, 2) : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">{{ $attempt->started_at->format('d M H:i:s') }}</td>
                                        <td class="px-4 py-3 text-gray-500">{{ $attempt->submitted_at?->format('d M H:i:s') ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
