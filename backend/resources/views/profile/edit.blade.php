<x-app-layout title="Profil">
    <div class="mx-auto max-w-4xl px-4 pb-12 pt-5 sm:px-6 sm:pt-8">

        {{-- Executive Profile Hero --}}
        <section class="relative overflow-hidden rounded-[1.75rem] bg-navy-950 p-6 text-white shadow-card ring-1 ring-white/10 sm:rounded-[2rem] sm:p-7">
            <div class="pointer-events-none absolute -right-16 -top-16 h-60 w-60 rounded-full bg-brand-600/25 blur-3xl" aria-hidden="true"></div>
            <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <span class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-indigo-600 text-2xl font-extrabold text-white shadow-glow-blue ring-2 ring-white/15">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="truncate text-xl font-extrabold tracking-tight text-white sm:text-2xl">{{ $user->name }}</h1>
                            @if ($user->hasVerifiedEmail())
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-400/15 px-2.5 py-0.5 text-[11px] font-bold text-emerald-300 ring-1 ring-emerald-400/30">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    Terverifikasi
                                </span>
                            @endif
                        </div>
                        <p class="mt-0.5 truncate text-sm text-slate-300">{{ $user->email }}</p>
                        <p class="mt-1 text-xs text-slate-400">
                            Bergabung {{ $user->created_at->locale('id')->translatedFormat('d M Y') }} · <span class="font-bold text-sky-300">{{ $user->practiceSessions()->count() }} sesi latihan</span>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-6 space-y-5">
            @foreach (['profile.partials.update-profile-information-form', 'profile.partials.update-password-form', 'profile.partials.delete-user-form'] as $partial)
                <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs sm:rounded-3xl sm:p-7">
                    <div class="max-w-xl">
                        @include($partial)
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
