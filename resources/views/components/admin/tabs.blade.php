@php
$tabs = [
    'admin.subjects' => 'Mata Pelajaran',
    'admin.questions' => 'Bank Soal',
    'admin.tryouts' => 'Tryout',
    'admin.results' => 'Hasil',
];
@endphp

<div class="border-b border-gray-200 mb-6">
    <nav class="-mb-px flex space-x-6" aria-label="Navigasi admin">
        @foreach ($tabs as $routeName => $label)
            <a
                href="{{ route($routeName) }}"
                wire:navigate
                class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium
                    {{ request()->routeIs($routeName) ? 'border-teal-600 text-teal-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                {{ $label }}
            </a>
        @endforeach
    </nav>
</div>
