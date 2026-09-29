<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <p class="text-gray-800">Halo, {{ auth()->user()->name }}.</p>

                @if (auth()->user()->isAdmin())
                    <p class="text-gray-600 mt-1 text-sm">
                        Anda login sebagai admin. Kelola mata pelajaran, bank soal, dan tryout dari menu Admin di navigasi atas.
                    </p>
                    <a href="{{ route('admin.subjects') }}" wire:navigate class="inline-flex items-center mt-4 px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-md hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                        Buka Panel Admin
                    </a>
                @else
                    <p class="text-gray-600 mt-1 text-sm">
                        Anda login sebagai peserta. Cek tryout yang sedang dibuka dan mulai mengerjakannya dari halaman Tryout.
                    </p>
                    <a href="{{ route('tryout.index') }}" wire:navigate class="inline-flex items-center mt-4 px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-md hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                        Lihat Daftar Tryout
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
