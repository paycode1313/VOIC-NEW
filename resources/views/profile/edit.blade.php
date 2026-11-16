<x-app-layout title="Profil">
    <div class="mx-auto max-w-3xl px-4 pb-10 pt-6 sm:px-6 sm:pt-8">

        {{-- Profile hero --}}
        <div class="flex items-center gap-4">
            <span class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-2xl font-extrabold text-white dark:bg-white dark:text-slate-900">
                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}
            </span>
            <div class="min-w-0">
                <h1 class="text-xl font-extrabold tracking-tight truncate sm:text-2xl">{{ $user->name }}</h1>
                <p class="mt-0.5 text-sm text-slate-500 truncate">{{ $user->email }}</p>
                <p class="mt-0.5 text-xs text-slate-400">Bergabung {{ $user->created_at->locale('id')->translatedFormat('d M Y') }} · {{ $user->practiceSessions()->count() }} sesi</p>
            </div>
        </div>

        <div class="mt-6 space-y-4">
            @foreach (['profile.partials.update-profile-information-form', 'profile.partials.update-password-form', 'profile.partials.delete-user-form'] as $partial)
                <section class="rounded-2xl border border-black/5 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03] sm:rounded-3xl sm:p-7">
                    <div class="max-w-xl">
                        @include($partial)
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
