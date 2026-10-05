<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- 1-Click Demo Login for Innofest Exhibition & Judges -->
    <div class="mb-6 p-4 rounded-2xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200/80 dark:border-indigo-800/60 text-center">
        <div class="flex items-center justify-center gap-1.5 text-xs font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider mb-1">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Akses Cepat Pengunjung & Juri Innofest
        </div>
        <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
            Coba demo interaktif tanpa repot mendaftar akun baru:
        </p>
        <button type="button"
                id="btnDemoLogin"
                onclick="fillDemoAccount()"
                class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold text-white bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 hover:from-indigo-500 hover:to-purple-500 rounded-xl shadow-md shadow-indigo-500/25 transition transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            1-Click Masuk Akun Demo
        </button>
    </div>

    <script>
        function fillDemoAccount() {
            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');
            if (emailInput && passInput) {
                emailInput.value = 'test@example.com';
                passInput.value = 'password';
                document.querySelector('form').submit();
            }
        }
    </script>

    <div class="relative my-4">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
        </div>
        <div class="relative flex justify-center text-xs uppercase">
            <span class="bg-white dark:bg-gray-800 px-2 text-gray-400">Atau masuk dengan akun Anda</span>
        </div>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
