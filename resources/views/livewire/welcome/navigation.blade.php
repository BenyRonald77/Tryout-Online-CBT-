<nav class="flex items-center gap-2">
    @auth
        <a
            href="{{ url('/dashboard') }}"
            class="rounded-md px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
        >
            Dashboard
        </a>
    @else
        <a
            href="{{ route('login') }}"
            class="rounded-md px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
        >
            Masuk
        </a>

        @if (Route::has('register'))
            <a
                href="{{ route('register') }}"
                class="rounded-md px-4 py-2 bg-teal-700 text-sm font-medium text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
            >
                Daftar
            </a>
        @endif
    @endauth
</nav>
