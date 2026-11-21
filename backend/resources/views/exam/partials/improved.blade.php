{{-- A stronger version of one answer, or the answer framework while the AI is still writing it ("pending").
     Rendered in the report and by ExamController::improvements(), whose block replaces the pending one. --}}
@php
    $pending = ($item['improved_source'] ?? null) === 'pending';
@endphp
<div data-improved-key="{{ $answerKey }}" class="rounded-2xl border border-emerald-500/20 bg-emerald-50 p-4 dark:bg-emerald-500/10"
     x-data="{ playing: false, canSpeak: 'speechSynthesis' in window }" @pagehide.window="window.speechSynthesis?.cancel()">
    <div class="flex items-center justify-between gap-3">
        <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">
            {{ $item['improved_source'] === 'ai' ? ($isEn ? 'A stronger version' : 'Versi jawaban yang lebih kuat') : ($isEn ? 'Suggested answer framework' : 'Kerangka jawaban yang disarankan') }}
        </p>
        @if ($pending && $loadable)
            {{-- The AI is writing the real one right now; the framework stays until it arrives (or if it never does). --}}
            <span data-improved-spinner class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-emerald-700 print:hidden">
                <span class="h-3 w-3 animate-spin rounded-full border-2 border-emerald-300 border-t-emerald-700" aria-hidden="true"></span>
                {{ $isEn ? 'Writing a stronger version…' : 'Lagi nulis versi yang lebih kuat…' }}
            </span>
        @endif
        @if ($item['improved_source'] === 'ai')
            {{-- Hear how it sounds: this is a speaking practice, the better answer should be heard, not only read. --}}
            <button type="button" x-show="canSpeak" x-cloak data-no-press
                    @click="if (playing) { window.speechSynthesis.cancel(); playing = false } else { playing = window.voicSpeak(@js($item['improved']), @js($language), () => playing = false) }"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-bold text-emerald-800 shadow-sm ring-1 ring-emerald-600/20 transition hover:bg-emerald-100 print:hidden"
                    :aria-pressed="playing.toString()">
                <svg x-show="!playing" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z"/></svg>
                <span x-show="playing" x-cloak class="voic-wave text-[11px]" aria-hidden="true"><i></i><i></i><i></i></span>
                <span x-text="playing ? @js($isEn ? 'Stop' : 'Berhenti') : @js($isEn ? 'Listen' : 'Dengarkan')"></span>
            </button>
        @endif
    </div>
    <p @class(['mt-1.5 leading-relaxed text-emerald-950 dark:text-emerald-100', 'opacity-70' => $pending && $loadable])>{{ $item['improved'] }}</p>
</div>
