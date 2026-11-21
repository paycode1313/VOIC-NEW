{{-- The coaching notes. While the AI is still writing them ("pending") the rule-based notes show with a small
     spinner; ExamController::improvements() renders this again with the AI's notes, which replace the block. --}}
@php
    $pending = ($report['summary_source'] ?? null) === 'pending' && $loadable;
@endphp
<div data-coach-notes>
@if ($pending)
    <p data-improved-spinner class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-slate-600 print:hidden">
        <span class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-slate-300 border-t-slate-700" aria-hidden="true"></span>
        {{ $isEn ? 'Your coach is writing notes about this conversation…' : 'Coach lagi nulis catatan dari obrolan tadi…' }}
    </p>
@endif
<section class="mt-4 grid gap-4 md:grid-cols-2">
    <div class="rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03]">
        <h2 class="font-bold">✅ {{ $isEn ? 'Already good' : 'Sudah bagus' }}</h2>
        <ul class="mt-3 space-y-2 text-sm text-slate-700 dark:text-slate-300">
            @foreach ($report['strengths'] as $item)
                <li class="flex gap-2"><span class="text-slate-400">•</span><span>{{ $item }}</span></li>
            @endforeach
        </ul>
    </div>
    <div class="rounded-3xl bg-indigo-50 p-6 text-slate-900 ring-1 ring-indigo-100">
        <h2 class="font-bold">🎯 {{ $isEn ? 'Practise next' : 'Latih berikutnya' }}</h2>
        <ul class="mt-3 space-y-2 text-sm opacity-90">
            @foreach ($report['practice'] as $item)
                <li class="flex gap-2"><span class="opacity-60">•</span><span>{{ $item }}</span></li>
            @endforeach
        </ul>
    </div>
</section>

@if (! empty($report['phrases']))
    <section class="mt-4 rounded-3xl border border-emerald-500/20 bg-emerald-50 p-6 dark:bg-emerald-500/10">
        <h2 class="font-bold text-emerald-900 dark:text-emerald-100">💬 {{ $isEn ? 'Sentences you can reuse' : 'Kalimat yang bisa kamu pakai' }}</h2>
        <ul class="mt-3 space-y-2">
            @foreach ($report['phrases'] as $phrase)
                <li class="rounded-2xl bg-white/70 px-4 py-3 text-[15px] leading-relaxed text-emerald-950 dark:bg-black/20 dark:text-emerald-100">“{{ $phrase }}”</li>
            @endforeach
        </ul>
    </section>
@endif
</div>
