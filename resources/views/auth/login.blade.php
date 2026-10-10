<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- 1-Click Demo Login for Evaluation -->
    <div class="mb-6 p-4 rounded-2xl bg-blue-50/80 dark:bg-navy-950/60 border border-blue-200/80 dark:border-navy-800 text-center">
        <div class="flex items-center justify-center gap-1.5 text-xs font-bold text-blue-700 dark:text-blue-300 uppercase tracking-wider mb-1">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Akses Cepat Mode Evaluator
        </div>
        <p class="text-xs text-slate-600 dark:text-slate-400 mb-3">
            Masuk langsung untuk meninjau seluruh fitur platform simulasi:
        </p>
        <button type="button"
                id="btnDemoLogin"
                onclick="fillDemoAccount()"
                class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold text-white bg-gradient-to-r from-blue-600 via-blue-700 to-navy-900 hover:from-blue-500 hover:to-navy-800 rounded-xl shadow-md shadow-blue-600/25 transition transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
            <svg class="w-4 h-4 me-1.5 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Masuk Akun Evaluasi (1-Klik)
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

    <div class="relative my-5">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-slate-200/80 dark:border-slate-800"></div>
        </div>
        <div class="relative flex justify-center text-[11px] font-semibold uppercase tracking-wider">
            <span class="bg-white dark:bg-[#0f1c2f] px-3 text-slate-400 dark:text-slate-500">Atau masuk dengan akun Anda</span>
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
                <input id="remember_me" type="checkbox" class="rounded dark:bg-navy-950 border-slate-300 dark:border-navy-700 text-blue-600 shadow-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:focus:ring-offset-navy-900" name="remember">
                <span class="ms-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-navy-900" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
