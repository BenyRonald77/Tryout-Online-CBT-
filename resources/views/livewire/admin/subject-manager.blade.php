<div>
    <div class="mb-6">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kelola Mata Pelajaran</h2>
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
                @if (! $showForm)
                    <button wire:click="create" class="px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-md hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-1">
                        Tambah Mata Pelajaran
                    </button>
                @endif
            </div>

            @if ($showForm)
                <form wire:submit="save" class="bg-white border border-gray-200 rounded-lg p-5 space-y-4">
                    <div>
                        <x-input-label for="name" value="Nama Mata Pelajaran" />
                        <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">Simpan</x-primary-button>
                        <x-secondary-button type="button" wire:click="cancel">Batal</x-secondary-button>
                    </div>
                </form>
            @endif

            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                @if ($subjects->isEmpty())
                    <div class="p-8 text-center text-gray-600">
                        Belum ada mata pelajaran. Tambahkan yang pertama di atas.
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-gray-600">
                            <tr>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Jumlah Soal</th>
                                <th class="px-4 py-3">Dipakai di Tryout</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($subjects as $subject)
                                <tr wire:key="subject-{{ $subject->id }}">
                                    <td class="px-4 py-3 text-gray-800">{{ $subject->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $subject->questions_count }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $subject->tryouts_count }}</td>
                                    <td class="px-4 py-3 text-right space-x-3">
                                        <button wire:click="edit({{ $subject->id }})" class="text-teal-700 hover:text-teal-900 underline text-sm">Ubah</button>
                                        <button
                                            wire:click="delete({{ $subject->id }})"
                                            wire:confirm="Hapus mata pelajaran '{{ $subject->name }}'?"
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
