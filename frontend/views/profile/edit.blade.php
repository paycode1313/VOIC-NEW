<x-app-layout title="Profil">
    <div class="mx-auto max-w-3xl px-4 pb-20 pt-8 sm:px-6">
        <div class="flex items-center gap-4">
            <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 text-xl font-extrabold text-white dark:bg-white dark:text-slate-900">
                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}
            </span>
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight">{{ $user->name }}</h1>
                <p class="text-sm text-slate-500">{{ $user->email }} · bergabung {{ $user->created_at->locale('id')->translatedFormat('d M Y') }} · {{ $user->practiceSessions()->count() }} sesi</p>
            </div>
        </div>

        <div class="mt-8 space-y-4">
            @foreach (['profile.partials.update-profile-information-form', 'profile.partials.update-password-form', 'profile.partials.delete-user-form'] as $partial)
                <section class="rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03] sm:p-8">
                    <div class="max-w-xl">
                        @include($partial)
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
