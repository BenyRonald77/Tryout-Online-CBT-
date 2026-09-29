<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Daftar Tryout
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-md px-4 py-3 text-sm" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @if ($tryouts->isEmpty())
                <div class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-600">
                    Belum ada tryout yang dipublikasikan. Silakan cek lagi nanti.
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($tryouts as $tryout)
                        <div class="bg-white rounded-lg border border-gray-200 p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold text-gray-900">{{ $tryout->title }}</h3>
                                    @if ($tryout->status === 'closed')
                                        <span class="text-xs font-medium px-2 py-0.5 rounded bg-gray-100 text-gray-700">Ditutup</span>
                                    @else
                                        <span class="text-xs font-medium px-2 py-0.5 rounded bg-teal-50 text-teal-700">Published</span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ $tryout->question_count }} soal &middot; {{ $tryout->duration_minutes }} menit
                                    &middot; jadwal {{ $tryout->starts_at->format('d M Y H:i') }} - {{ $tryout->ends_at->format('d M Y H:i') }}
                                </p>
                            </div>

                            <div class="shrink-0">
                                @if ($tryout->myAttempt)
                                    @if ($tryout->myAttempt->status === 'ongoing')
                                        <a href="{{ route('tryout.kerjakan', $tryout) }}"
                                           class="inline-flex items-center px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-md hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                                            Lanjutkan Mengerjakan
                                        </a>
                                    @else
                                        <a href="{{ route('tryout.hasil', $tryout) }}"
                                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                                            Lihat Hasil Saya
                                        </a>
                                    @endif
                                @elseif ($tryout->isOpenForStart())
                                    <form method="POST" action="{{ route('tryout.mulai', $tryout) }}">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-md hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                                            Mulai Tryout
                                        </button>
                                    </form>
                                @elseif ($tryout->status === 'closed')
                                    <span class="text-sm text-gray-500">Sudah ditutup, Anda tidak mengikuti.</span>
                                @elseif (now()->lessThan($tryout->starts_at))
                                    <span class="text-sm text-gray-500">Belum dibuka.</span>
                                @else
                                    <span class="text-sm text-gray-500">Jendela waktu sudah lewat.</span>
                                @endif

                                @if ($tryout->status === 'closed')
                                    <a href="{{ route('tryout.ranking', $tryout) }}" class="block mt-2 text-sm text-teal-700 hover:text-teal-800 underline text-center">
                                        Lihat Peringkat
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
