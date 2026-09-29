<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Tryout Online CBT</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans">
        <div class="min-h-screen bg-gray-50 flex flex-col">
            <header class="max-w-5xl w-full mx-auto px-6 py-6 flex items-center justify-between">
                <span class="font-semibold text-gray-900">Tryout Online CBT</span>
                @if (Route::has('login'))
                    <livewire:welcome.navigation />
                @endif
            </header>

            <main class="flex-1 flex items-center">
                <div class="max-w-5xl w-full mx-auto px-6 py-12">
                    <div class="max-w-xl">
                        <h1 class="text-3xl font-bold text-gray-900">Tryout Online CBT</h1>
                        <p class="mt-4 text-gray-600">
                            Platform ujian simulasi berbasis komputer. Peserta mengerjakan tryout dengan soal yang
                            diacak dan waktu yang ditegakkan oleh server, lalu melihat peringkat begitu tryout ditutup.
                            Admin mengelola bank soal dan paket tryout dari satu tempat.
                        </p>

                        <div class="mt-8 flex items-center gap-3">
                            @auth
                                <a
                                    href="{{ url('/dashboard') }}"
                                    class="inline-flex items-center px-5 py-2.5 bg-teal-700 text-white text-sm font-medium rounded-md hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                                >
                                    Buka Dashboard
                                </a>
                            @else
                                <a
                                    href="{{ route('login') }}"
                                    class="inline-flex items-center px-5 py-2.5 bg-teal-700 text-white text-sm font-medium rounded-md hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                                >
                                    Masuk
                                </a>
                                @if (Route::has('register'))
                                    <a
                                        href="{{ route('register') }}"
                                        class="inline-flex items-center px-5 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                                    >
                                        Daftar Sebagai Peserta
                                    </a>
                                @endif
                            @endauth
                        </div>
                    </div>
                </div>
            </main>

            <footer class="max-w-5xl w-full mx-auto px-6 py-8 text-sm text-gray-400">
                Tryout Online CBT
            </footer>
        </div>
    </body>
</html>
