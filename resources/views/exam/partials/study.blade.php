{{-- User trial: progress banner, and the questionnaire after the third scored practice. --}}
@php
    $isEn = ($language ?? 'id') === 'en';
@endphp
<section class="mt-6 rounded-3xl border border-sky-200 bg-sky-50 p-6 text-slate-900 dark:border-sky-400/20 dark:bg-sky-400/10 dark:text-white">
    @if (session('status') === 'study-survey-saved')
        <p class="font-semibold">{{ $isEn ? 'Thank you! Your answers were saved.' : 'Terima kasih! Jawabanmu sudah tersimpan.' }}</p>
    @elseif ($study['survey'])
        <h2 class="text-lg font-extrabold">{{ $isEn ? 'Last step: 6 quick questions' : 'Langkah terakhir: 6 pertanyaan singkat' }}</h2>
        <p class="mt-1 text-sm opacity-80">{{ $isEn ? "You've finished {$study['total']} practices. 1 = strongly disagree, 5 = strongly agree." : "Kamu sudah menyelesaikan {$study['total']} kali latihan. 1 = sangat tidak setuju, 5 = sangat setuju." }}</p>
        <form method="POST" action="{{ route('exam.survey', $session) }}" class="mt-5 space-y-5">
            @csrf
            @foreach (\App\Services\Study\UserStudy::QUESTIONS as $key => $question)
                <fieldset>
                    <legend class="text-sm font-semibold">{{ $loop->iteration }}. {{ $question[$isEn ? 'en' : 'id'] }}</legend>
                    <div class="mt-2 flex gap-2">
                        @foreach (range(1, 5) as $value)
                            <label class="cursor-pointer">
                                <input type="radio" name="answers[{{ $key }}]" value="{{ $value }}" class="peer sr-only" @checked((int) old("answers.{$key}") === $value) required>
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl border border-black/10 bg-white font-bold peer-checked:border-slate-900 peer-checked:bg-slate-900 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-sky-500 dark:border-white/10 dark:bg-white/5 dark:peer-checked:bg-white dark:peer-checked:text-slate-900">{{ $value }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
            <x-input-error :messages="$errors->get('answers.*')" class="mt-2" />
            <div>
                <label for="study_comment" class="text-sm font-semibold">{{ $isEn ? 'Anything else? (optional)' : 'Ada masukan lain? (opsional)' }}</label>
                <textarea id="study_comment" name="comment" rows="3" maxlength="1000" class="mt-2 w-full rounded-xl border-black/10 bg-white px-4 py-3 text-[15px] dark:border-white/10 dark:bg-white/5">{{ old('comment') }}</textarea>
            </div>
            <button type="submit" class="rounded-full bg-slate-900 px-6 py-3 text-sm font-bold text-white dark:bg-white dark:text-slate-900">{{ $isEn ? 'Send answers' : 'Kirim jawaban' }}</button>
        </form>
    @else
        <p class="font-semibold">
            {{ $isEn ? 'User trial' : 'Uji coba' }} · {{ $study['code'] }}
            @if ($study['attempt'])
                · {{ $isEn ? "practice {$study['attempt']} of {$study['total']}" : "latihan ke-{$study['attempt']} dari {$study['total']}" }}
            @endif
        </p>
        @if (($study['attempt'] ?? 0) < $study['total'])
            <p class="mt-1 text-sm opacity-80">{{ $isEn ? 'Do another practice with the same code to see your progress.' : 'Lanjutkan latihan berikutnya dengan kode yang sama untuk melihat kemajuanmu.' }}</p>
        @endif
    @endif
</section>
